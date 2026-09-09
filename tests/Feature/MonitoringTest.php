<?php

use App\Data\Monitoring\EventInputData;
use App\Jobs\CheckProjectHealth;
use App\Jobs\ProcessAppEvent;
use App\Jobs\ReplayPendingEvents;
use App\Jobs\ScheduleHealthChecks;
use App\Models\AppEvent;
use App\Models\DetectionRule;
use App\Models\HealthCheck;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\EventIngestor;
use App\Services\HealthMonitor;
use App\Services\IncidentDetector;
use App\Services\SafeEndpoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
        'database.connections.sqlite.url' => null, 'queue.default' => 'sync', 'cache.default' => 'array']);
    DB::purge('sqlite');
    expect(DB::connection()->getDatabaseName())->toBe(':memory:');
    $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    $user = User::factory()->create();
    $this->organization = Organization::create(['name' => 'Tenant', 'slug' => 'tenant', 'active' => true, 'owner_id' => $user->id]);
    $this->token = str_repeat('a', 64);
    $this->project = Project::create(['name' => 'API', 'slug' => 'api', 'active' => true, 'organization_id' => $this->organization->id]);
    $this->project->forceFill(['api_token' => hash('sha256', $this->token), 'github_secret' => 'webhook-secret'])->save();
    Queue::fake();
    Http::preventStrayRequests();
});

it('authenticates hashed project tokens and durably deduplicates deliveries', function () {
    $this->postJson('/api/v1/events', ['external_id' => 'one', 'type' => 'job.failed'])->assertUnauthorized();
    $first = $this->withToken($this->token)->postJson('/api/v1/events', ['external_id' => 'one', 'type' => 'job.failed'])->assertStatus(202);
    $this->withToken($this->token)->postJson('/api/v1/events', ['external_id' => 'one', 'type' => 'changed'])->assertStatus(202)->assertJsonPath('data.id', $first->json('data.id'));
    expect(AppEvent::count())->toBe(1)->and(AppEvent::first()->type)->toBe('job.failed');
    Queue::assertPushed(ProcessAppEvent::class);
    $this->withToken(hash('sha256', $this->token))->postJson('/api/v1/events', ['external_id' => 'two', 'type' => 'job.failed'])->assertUnauthorized();
});

it('validates events and accepts idempotency headers', function () {
    $this->withToken($this->token)->postJson('/api/v1/events', ['type' => 'job.failed'])->assertUnprocessable();
    $this->withToken($this->token)->withHeader('Idempotency-Key', 'header-key')
        ->postJson('/api/v1/events', ['type' => 'job.failed', 'severity' => 'error'])->assertStatus(202);
    expect(AppEvent::first()->external_id)->toBe('header-key');
});

it('rejects inactive projects and tenants and deleted projects', function () {
    $this->project->update(['active' => false]);
    $this->withToken($this->token)->getJson('/api/v1/status')->assertForbidden();
    $this->project->update(['active' => true]);
    $this->organization->update(['active' => false]);
    $this->withToken($this->token)->getJson('/api/v1/status')->assertForbidden();
    $this->project->delete();
    $this->withToken($this->token)->getJson('/api/v1/status')->assertUnauthorized();
});

it('verifies raw GitHub HMAC and deduplicates delivery IDs', function () {
    $body = '{"deployment_status":{"state":"failure"}}';
    $url = '/api/v1/webhooks/github/'.$this->project->id;
    $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_GITHUB_EVENT' => 'deployment_status', 'HTTP_X_GITHUB_DELIVERY' => 'delivery-one'];
    $this->call('POST', $url, [], [], [], $headers, $body)->assertUnauthorized();
    $headers['HTTP_X_HUB_SIGNATURE_256'] = 'sha256='.hash_hmac('sha256', $body, 'webhook-secret');
    $this->call('POST', $url, [], [], [], $headers, $body)->assertStatus(202);
    $this->call('POST', $url, [], [], [], $headers, $body)->assertStatus(202);
    expect(AppEvent::count())->toBe(1)->and(AppEvent::first()->source)->toBe('github')->and(AppEvent::first()->severity)->toBe('error');
    expect(DB::table('projects')->value('github_secret'))->not->toBe('webhook-secret');
    $this->call('POST', $url, [], [], [], $headers, $body.' ')->assertUnauthorized();
});

it('groups threshold events and replay cannot double count them', function () {
    DetectionRule::create(['project_id' => $this->project->id, 'name' => 'Job errors', 'event_type' => 'job.failed', 'threshold' => 2, 'window_seconds' => 300, 'severity' => 'high']);
    $ingestor = app(EventIngestor::class);
    $detector = app(IncidentDetector::class);
    $one = $ingestor->ingest($this->project, EventInputData::from(['external_id' => 'one', 'type' => 'job.failed']));
    $detector->process($one->id);
    expect(Incident::count())->toBe(0);
    $two = $ingestor->ingest($this->project, EventInputData::from(['external_id' => 'two', 'type' => 'job.failed']));
    $detector->process($two->id);
    $detector->process($two->id);
    expect(Incident::count())->toBe(1)->and(Incident::first()->event_count)->toBe(2)->and($this->project->fresh()->status)->toBe('degraded');
    expect($one->fresh()->incident_id)->toBe($two->fresh()->incident_id);
    $three = $ingestor->ingest($this->project, EventInputData::from(['external_id' => 'three', 'type' => 'job.failed']));
    $detector->process($three->id);
    expect(Incident::first()->event_count)->toBe(3);
});

it('rolls back partial processing and recovers via the durable inbox', function () {
    config(['eventide.event_threshold' => 1]);
    $event = app(EventIngestor::class)->ingest($this->project, EventInputData::from(['external_id' => 'crash', 'type' => 'job.failed']));
    $crashing = new class extends IncidentDetector
    {
        public function refreshProjectStatus(Project $project): string
        {
            throw new RuntimeException('Simulated worker crash');
        }
    };
    expect(fn () => $crashing->process($event->id))->toThrow(RuntimeException::class);
    expect(Incident::count())->toBe(0)->and($event->fresh()->processed_at)->toBeNull();
    Queue::fake();
    (new ReplayPendingEvents)->handle();
    Queue::assertPushed(ProcessAppEvent::class, fn ($job) => $job->eventId === $event->id);
    (new ProcessAppEvent($event->id))->handle(app(IncidentDetector::class));
    (new ProcessAppEvent($event->id))->handle(app(IncidentDetector::class));
    expect(Incident::first()->event_count)->toBe(1)->and($event->fresh()->processed_at)->not->toBeNull();
});

it('keeps accepted events when queue dispatch fails', function () {
    Queue::shouldReceive('connection')->andThrow(new RuntimeException('Broker unavailable'));
    $this->withToken($this->token)->postJson('/api/v1/events', ['external_id' => 'outage', 'type' => 'job.failed'])->assertStatus(202);
    expect(AppEvent::whereNull('processed_at')->count())->toBe(1);
});

it('does not process inactive tenant events until replay after reactivation', function () {
    $event = app(EventIngestor::class)->ingest($this->project, EventInputData::from(['external_id' => 'pending', 'type' => 'job.failed']));
    $this->organization->update(['active' => false]);
    app(IncidentDetector::class)->process($event->id);
    expect($event->fresh()->processed_at)->toBeNull();
    Queue::fake();
    (new ReplayPendingEvents)->handle();
    Queue::assertNothingPushed();
    $this->organization->update(['active' => true]);
    (new ReplayPendingEvents)->handle();
    Queue::assertPushed(ProcessAppEvent::class);
});

it('enforces receipt windows and disabled rules', function () {
    config(['eventide.event_threshold' => 2]);
    $one = app(EventIngestor::class)->ingest($this->project, EventInputData::from(['external_id' => 'old', 'type' => 'job.failed']));
    $one->forceFill(['created_at' => now()->subMinutes(10)])->save();
    $two = app(EventIngestor::class)->ingest($this->project, EventInputData::from(['external_id' => 'new', 'type' => 'job.failed']));
    app(IncidentDetector::class)->process($two->id);
    expect(Incident::count())->toBe(0);
    DetectionRule::create(['project_id' => $this->project->id, 'name' => 'Disabled', 'event_type' => 'job.failed', 'threshold' => 1, 'enabled' => false]);
    $three = app(EventIngestor::class)->ingest($this->project, EventInputData::from(['external_id' => 'disabled', 'type' => 'job.failed']));
    app(IncidentDetector::class)->process($three->id);
    expect(Incident::count())->toBe(0);
});

it('rejects non-public addresses and unsafe endpoint syntax', function (string $url) {
    expect(fn () => app(SafeEndpoint::class)->validate($url))->toThrow(InvalidArgumentException::class);
})->with(['http://127.0.0.1/health', 'http://10.1.2.3', 'http://169.254.169.254', 'http://100.64.0.1',
    'http://[::1]', 'http://[::ffff:127.0.0.1]', 'http://[2002:7f00:1::]', 'http://192.0.2.1', 'http://224.0.0.1',
    'file:///etc/passwd', 'http://user:password@example.com', 'https://example.com:8080', 'http://example.com\\@127.0.0.1']);

it('rejects mixed public and private DNS answers', function () {
    $endpoint = new class extends SafeEndpoint
    {
        protected function resolve(string $host): array
        {
            return ['8.8.8.8', '127.0.0.1'];
        }
    };
    expect(fn () => $endpoint->validate('https://example.com/health'))->toThrow(InvalidArgumentException::class);
});

it('pins public DNS and disables redirects and proxies', function () {
    $endpoint = new class extends SafeEndpoint
    {
        protected function resolve(string $host): array
        {
            return ['8.8.8.8'];
        }
    };
    Http::fake(function ($request, $options) {
        expect($options['curl'][CURLOPT_RESOLVE])->toBe(['example.com:443:8.8.8.8'])
            ->and($options['allow_redirects'])->toBeFalse()->and($options['proxy'])->toBe('')
            ->and($options['verify'])->toBeTrue();

        return Http::response('', 302, ['Location' => 'http://127.0.0.1']);
    });
    expect($endpoint->get('https://example.com/health')->status())->toBe(302);
    Http::assertSentCount(1);
});

it('records failures, groups health incidents across lifecycle states, and retains manual resolution', function () {
    $this->project->update(['check_status_url' => 'https://8.8.8.8/health']);
    Http::fake(['*' => Http::sequence()->push('', 503)->push('', 503)->push('', 503)->push('', 503)->push('', 200)]);
    $monitor = app(HealthMonitor::class);
    $monitor->check($this->project);
    expect($this->project->fresh()->status)->toBe('degraded');
    $monitor->check($this->project);
    $monitor->check($this->project);
    expect($this->project->fresh()->status)->toBe('down')->and(Incident::count())->toBe(1);
    Incident::first()->update(['status' => 'identified']);
    $monitor->check($this->project);
    expect(Incident::count())->toBe(1)->and(HealthCheck::count())->toBe(4);
    $monitor->check($this->project);
    expect($this->project->fresh()->status)->toBe('degraded')->and($this->project->fresh()->consecutive_failures)->toBe(0);
    Incident::first()->update(['status' => 'resolved', 'resolved_at' => now()]);
    expect(app(IncidentDetector::class)->refreshProjectStatus($this->project))->toBe('operational');
    $this->travel(6)->minutes();
    expect(app(IncidentDetector::class)->refreshProjectStatus($this->project))->toBe('unknown');
});

it('schedules active checks and scopes history to the bearer project', function () {
    $this->project->update(['check_status_url' => 'https://8.8.8.8/health']);
    $other = Project::create(['name' => 'Other', 'slug' => 'other', 'organization_id' => $this->organization->id, 'active' => false, 'check_status_url' => 'https://8.8.4.4']);
    HealthCheck::create(['project_id' => $other->id, 'status' => 'down', 'checked_at' => now()]);
    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));
    Queue::assertPushed(CheckProjectHealth::class, 1);
    Queue::assertPushed(CheckProjectHealth::class, fn ($job) => $job->projectId === $this->project->id);
    $this->withToken($this->token)->getJson('/api/v1/health-checks')->assertOk()->assertJsonCount(0, 'data');
    $this->withToken($this->token)->getJson('/api/v1/status')->assertOk()->assertJsonPath('data.status', 'unknown');
});

it('isolates delivery keys and detection counts between projects', function () {
    config(['eventide.event_threshold' => 2]);
    $other = Project::create(['name' => 'Other', 'slug' => 'other', 'organization_id' => $this->organization->id, 'active' => true]);
    $first = app(EventIngestor::class)->ingest($this->project, EventInputData::from(['external_id' => 'shared', 'type' => 'job.failed']));
    $second = app(EventIngestor::class)->ingest($other, EventInputData::from(['external_id' => 'shared', 'type' => 'job.failed']));
    app(IncidentDetector::class)->process($first->id);
    app(IncidentDetector::class)->process($second->id);
    expect(AppEvent::count())->toBe(2)->and(Incident::count())->toBe(0);
    $this->withToken($this->token)->postJson('/api/v1/events', [
        'external_id' => 'third', 'type' => 'job.failed', 'project_id' => $other->id,
    ])->assertStatus(202);
    expect(AppEvent::latest('id')->first()->project_id)->toBe($this->project->id);
});

it('records a blocked endpoint without making any HTTP request', function () {
    $this->project->update(['check_status_url' => 'http://169.254.169.254']);
    $check = app(HealthMonitor::class)->check($this->project);
    expect($check->status)->toBe('down')->and($check->response_status)->toBeNull()
        ->and($check->error)->toContain('public addresses');
    Http::assertNothingSent();
});

it('honors project check settings and skips duplicate queued health jobs', function () {
    $this->project->forceFill(['check_status_url' => 'https://8.8.8.8/health',
        'timeout_seconds' => 2, 'failure_threshold' => 1, 'check_interval_seconds' => 120])->save();
    Http::fake(function ($request, $options) {
        expect($options['timeout'])->toBe(2);

        return Http::response('', 503);
    });
    $job = new CheckProjectHealth($this->project->id);
    $job->handle(app(HealthMonitor::class));
    $job->handle(app(HealthMonitor::class));
    expect(HealthCheck::count())->toBe(1)->and($this->project->fresh()->status)->toBe('down');
    Http::assertSentCount(1);
    $this->travel(121)->seconds();
    $job->handle(app(HealthMonitor::class));
    expect(HealthCheck::count())->toBe(2)->and(Incident::count())->toBe(1);
});

it('waits until the exact configured interval before scheduling another check', function () {
    $this->freezeSecond();
    $this->project->forceFill([
        'check_status_url' => 'https://8.8.8.8/health',
        'check_interval_seconds' => 120,
        'last_checked_at' => now()->subSeconds(119),
    ])->save();

    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));

    Queue::assertNothingPushed();

    $this->travel(1)->seconds();
    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));

    Queue::assertPushed(CheckProjectHealth::class, 1);
    Queue::assertPushed(CheckProjectHealth::class, fn ($job) => $job->projectId === $this->project->id);
});

it('keeps a single pending check per project across scheduler runs', function () {
    $this->project->update(['check_status_url' => 'https://8.8.8.8/health']);
    $other = Project::create([
        'name' => 'Other', 'slug' => 'other', 'active' => true,
        'organization_id' => $this->organization->id,
        'check_status_url' => 'https://8.8.4.4/health',
    ]);

    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));
    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));

    Queue::assertPushed(CheckProjectHealth::class, 2);
    Queue::assertPushed(CheckProjectHealth::class, fn ($job) => $job->projectId === $this->project->id);
    Queue::assertPushed(CheckProjectHealth::class, fn ($job) => $job->projectId === $other->id);
});

it('recovers scheduling when an abandoned health check lock expires', function () {
    $this->freezeSecond();
    $this->project->update(['check_status_url' => 'https://8.8.8.8/health']);
    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));

    $this->travel(3599)->seconds();
    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));
    Queue::assertPushed(CheckProjectHealth::class, 1);

    $this->travel(2)->seconds();
    (new ScheduleHealthChecks)->handle(app(IncidentDetector::class));

    Queue::assertPushed(CheckProjectHealth::class, 2);
});

it('does not contact an endpoint disabled after its check was queued', function (string $change) {
    $this->project->update(['check_status_url' => 'https://8.8.8.8/health']);
    $job = new CheckProjectHealth($this->project->id);
    match ($change) {
        'project disabled' => $this->project->update(['active' => false]),
        'organization disabled' => $this->organization->update(['active' => false]),
        'endpoint removed' => $this->project->update(['check_status_url' => null]),
        'project deleted' => $this->project->delete(),
        'organization deleted' => $this->organization->delete(),
    };

    $job->handle(app(HealthMonitor::class));

    Http::assertNothingSent();
    $this->assertDatabaseCount('health_checks', 0);
})->with(['project disabled', 'organization disabled', 'endpoint removed', 'project deleted', 'organization deleted']);
