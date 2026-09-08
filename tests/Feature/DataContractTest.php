<?php

use App\Data\Monitoring\EventInputData;
use App\Data\Project\ProjectInputData;
use App\Data\Project\ResponseProjectData;
use App\Models\IncidentNote;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('project input DTO excludes privileged fields and preserves omitted settings', function () {
    $owner = User::factory()->create();
    $organization = Organization::create(['name' => 'Platform', 'slug' => 'platform', 'owner_id' => $owner->id, 'active' => true]);
    $project = $organization->projects()->create(['name' => 'Payments', 'slug' => 'payments', 'check_interval_seconds' => 600, 'active' => false]);

    $this->actingAs($owner)->post('/organization/platform/project/payments', [
        'name' => 'Payments API', 'api_token' => 'injected', 'organization_id' => 999,
    ])->assertSessionHasNoErrors();

    expect($project->fresh())
        ->name->toBe('Payments API')
        ->active->toBeFalse()
        ->check_interval_seconds->toBe(600)
        ->organization_id->toBe($organization->id)
        ->api_token->toBeNull();
    expect(ProjectInputData::from(['name' => 'Payments', 'api_token' => 'injected'])->toArray())->toBe(['name' => 'Payments']);
});

test('workspace DTOs expose only intended project attributes including nullable fields', function () {
    $owner = User::factory()->create();
    $organization = Organization::create(['name' => 'Platform', 'slug' => 'platform', 'owner_id' => $owner->id, 'active' => true]);
    $project = $organization->projects()->create(['name' => 'Payments', 'slug' => 'payments']);
    $project->forceFill(['api_token' => hash('sha256', 'private-token'), 'github_secret' => 'private-secret'])->save();

    $this->actingAs($owner)->get('/organization/platform/project/payments')->assertInertia(fn (Assert $page) => $page
        ->component('project/Show')
        ->where('project.base_url', null)
        ->where('project.has_api_token', true)
        ->where('project.has_github_secret', true)
        ->missing('project.api_token')
        ->missing('project.github_secret')
        ->missing('project.organization_id')
        ->missing('project.created_at')
        ->missing('auth.user.password'));
    expect(ResponseProjectData::fromModel($project->fresh())->toArray())
        ->toHaveKey('base_url', null)
        ->toHaveKey('manager_membership', null);
});

test('incident notes and state changes are persisted and exposed through DTOs', function () {
    $this->freezeTime();
    $owner = User::factory()->create();
    $organization = Organization::create(['name' => 'Platform', 'slug' => 'platform', 'owner_id' => $owner->id, 'active' => true]);
    $project = $organization->projects()->create(['name' => 'Payments', 'slug' => 'payments']);
    $base = '/organization/platform/project/payments';
    $this->actingAs($owner)->post($base.'/incidents', ['title' => 'Payments unavailable', 'severity' => 'high'])->assertSessionHasNoErrors();
    $incident = $project->incidents()->firstOrFail();
    $url = $base.'/incidents/'.$incident->id;

    $this->post($url.'/notes', ['body' => 'Investigating database connection saturation.', 'user_id' => 999])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('incident_notes', ['incident_id' => $incident->id, 'user_id' => $owner->id, 'body' => 'Investigating database connection saturation.']);
    $this->post($url.'/status', ['status' => 'resolved'])->assertSessionHasNoErrors();
    expect($incident->fresh()->resolved_at)->not->toBeNull();
    expect(IncidentNote::where('incident_id', $incident->id)->count())->toBe(2);
    $this->post($url.'/status', ['status' => 'resolved'])->assertSessionHasNoErrors();
    expect(IncidentNote::where('incident_id', $incident->id)->count())->toBe(2);
    $this->get($url)->assertInertia(fn (Assert $page) => $page->component('incident/Show')
        ->where('incident.status', 'resolved')->has('notes', 2)
        ->where('notes.0.user.id', $owner->id)->missing('notes.0.user.password')
        ->missing('notes.0.user_id')->missing('incident.fingerprint'));
    $this->actingAs(User::factory()->create())->post($url.'/notes', ['body' => 'Forbidden'])->assertForbidden();
    expect(IncidentNote::where('incident_id', $incident->id)->count())->toBe(2);
});

test('event input DTO keeps nested payload while excluding tenant and processing control fields', function () {
    $data = EventInputData::from([
        'external_id' => 'delivery-1', 'type' => 'payment.failed',
        'payload' => ['payment' => ['id' => 'pay_42']],
        'project_id' => 999, 'processed_at' => '2026-01-01', 'incident_id' => 123,
    ]);

    expect($data->toArray())->toBe([
        'external_id' => 'delivery-1', 'type' => 'payment.failed', 'severity' => 'error',
        'message' => null, 'payload' => ['payment' => ['id' => 'pay_42']], 'occurred_at' => null,
    ]);
});

test('dashboard retains older unresolved incidents when recent resolved incidents fill the history limit', function () {
    $owner = User::factory()->create();
    $organization = Organization::create(['name' => 'Platform', 'slug' => 'platform', 'owner_id' => $owner->id, 'active' => true]);
    $project = $organization->projects()->create(['name' => 'Payments', 'slug' => 'payments']);
    $open = $project->incidents()->create(['title' => 'Still unavailable', 'fingerprint' => hash('sha256', 'open'), 'opened_at' => now(), 'severity' => 'critical']);
    for ($index = 0; $index < 51; $index++) {
        $project->incidents()->create(['title' => 'Resolved', 'fingerprint' => hash('sha256', (string) $index), 'opened_at' => now(), 'resolved_at' => now(), 'status' => 'resolved']);
    }

    $this->actingAs($owner)->get('/')->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Index')->where('stats.open_incidents', 1)
        ->has('incidents', 1)->where('incidents.0.id', $open->id));
});

test('event response DTO serializes a populated project without its credentials', function () {
    $owner = User::factory()->create();
    $organization = Organization::create(['name' => 'Platform', 'slug' => 'platform', 'owner_id' => $owner->id, 'active' => true]);
    $project = $organization->projects()->create(['name' => 'Payments', 'slug' => 'payments']);
    $project->forceFill(['api_token' => hash('sha256', 'private-token')])->save();
    $project->events()->create([
        'external_id' => 'delivery-1', 'source' => 'application', 'type' => 'payment.failed',
        'severity' => 'error', 'occurred_at' => now(), 'payload' => ['payment_id' => 'pay_42'],
    ]);

    $this->actingAs($owner)->get('/?tab=events')->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Index')->has('events', 1)
        ->where('events.0.type', 'payment.failed')
        ->where('events.0.payload.payment_id', 'pay_42')
        ->where('events.0.project.name', 'Payments')
        ->missing('events.0.external_id')->missing('events.0.processed_at')
        ->missing('events.0.project.api_token')->missing('events.0.project.organization_id'));
});
