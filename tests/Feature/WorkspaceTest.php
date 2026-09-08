<?php

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    expect(config('database.default'))->toBe('sqlite');
    expect(config('database.connections.sqlite.database'))->toBe(':memory:');
    $this->withoutVite();
    Cache::flush();
});

function workspaceTenant(User $owner, string $slug): Organization
{
    $organization = Organization::create(['name' => ucfirst($slug), 'slug' => $slug, 'owner_id' => $owner->id, 'active' => true]);
    workspaceMember($organization, $owner, 'owner');

    return $organization;
}

function workspaceMember(Organization $organization, User $user, string $role): void
{
    $role = Role::findOrCreate($role, 'web');
    UserOrganization::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'role_id' => $role->id]);
}

test('guests are redirected and registration logs in with a hashed password', function () {
    $this->get('/')->assertRedirect('/login');
    $this->post('/register', ['name' => 'New Member', 'email' => 'NEW@example.com', 'password' => 'secure-password', 'password_confirmation' => 'secure-password'])->assertRedirect('/');
    $user = User::where('email', 'new@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('secure-password', $user->password))->toBeTrue();
    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
    $this->post('/login', ['email' => 'new@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->post('/login', ['email' => 'new@example.com', 'password' => 'secure-password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('web mutations require csrf outside the framework test bypass', function () {
    $this->app->bind(PreventRequestForgery::class, function ($app) {
        return new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };
    });
    $this->post('/login', ['email' => 'test@example.com', 'password' => 'password'])->assertStatus(419);
    $this->actingAs(User::factory()->create())->post('/organization', ['name' => 'Blocked'])->assertStatus(419);
    $this->withSession(['_token' => 'valid-token'])->post('/organization', ['name' => 'Allowed', '_token' => 'valid-token'])->assertRedirect('/?organization=allowed');
});

test('registration validates confirmation and duplicate email and login is throttled', function () {
    User::factory()->create(['email' => 'exists@example.com']);
    $this->post('/register', ['name' => 'Test', 'email' => 'exists@example.com', 'password' => '12345678', 'password_confirmation' => 'different'])->assertSessionHasErrors(['email', 'password']);
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'incorrect'])->assertSessionHasErrors('email');
    }
    $this->post('/login', ['email' => 'unknown@example.com', 'password' => 'incorrect'])->assertStatus(429);
});

test('workspace and project bindings isolate tenants', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $a = workspaceTenant($owner, 'alpha');
    $b = workspaceTenant($other, 'beta');
    $project = $a->projects()->create(['name' => 'Visible', 'slug' => 'visible']);
    $foreign = $b->projects()->create(['name' => 'Secret', 'slug' => 'secret']);
    $this->actingAs($owner)->get('/?organization=alpha&tab=projects')->assertInertia(fn (Assert $page) => $page
        ->component('workspace/Index', false)->has('organizations', 1)->where('organization.id', $a->id)->where('activeTab', 'projects')->has('projects', 1)->where('projects.0.id', $project->id));
    $this->get('/?organization=beta')->assertForbidden();
    $this->get('/organization/alpha/project/secret')->assertNotFound();
    $this->post('/organization/alpha/project/secret', ['name' => 'Hijacked'])->assertNotFound();
    $this->get('/organization/beta/project/secret')->assertForbidden();
    expect($foreign->fresh()->name)->toBe('Secret');
});

test('owners manage organizations and members while managers only manage projects', function () {
    $owner = User::factory()->create();
    $manager = User::factory()->create();
    $member = User::factory()->create();
    $this->actingAs($owner)->post('/organization', ['name' => 'Acme'])->assertRedirect('/?organization=acme');
    $organization = Organization::where('slug', 'acme')->firstOrFail();
    $this->post('/organization/acme/members', ['email' => $manager->email, 'role' => 'project-manager'])->assertSessionHasNoErrors();
    $this->post('/organization/acme/members', ['email' => $member->email, 'role' => 'member'])->assertSessionHasNoErrors();
    $this->post('/organization/acme/members', ['email' => 'unregistered@example.com', 'role' => 'member'])->assertSessionHasErrors('email');
    $this->delete('/organization/acme/members/'.$owner->id)->assertSessionHasErrors('member');
    $this->post('/organization/acme/members', ['email' => $owner->email, 'role' => 'member'])->assertSessionHasErrors('role');
    $this->actingAs($manager)->post('/organization/acme/project', ['name' => 'Payments'])->assertRedirect('/organization/acme/project/payments');
    $this->post('/organization/acme/project/payments', ['name' => 'Payments API'])->assertSessionHasNoErrors();
    $this->delete('/organization/acme/project/payments')->assertForbidden();
    $this->post('/organization/acme', ['name' => 'Renamed'])->assertForbidden();
    $this->post('/organization/acme/members', ['email' => $member->email, 'role' => 'owner'])->assertForbidden();
    $this->actingAs($member)->post('/organization/acme/project', ['name' => 'Forbidden'])->assertForbidden();
    $this->post('/organization/acme/project/payments', ['name' => 'Forbidden'])->assertForbidden();
    $this->actingAs($owner)->delete('/organization/acme/members/'.$member->id)->assertSessionHasNoErrors();
    $this->actingAs($member)->get('/?organization=acme')->assertForbidden();
    $this->actingAs($owner)->delete('/organization/acme')->assertRedirect('/?tab=organizations');
    $this->get('/organization/acme/project/payments')->assertNotFound();
    $this->post('/organization/acme/restore')->assertRedirect('/?organization=acme&tab=organizations');
    expect($organization->fresh()->active)->toBeTrue();
    expect($organization->projects()->count())->toBe(1);
});

test('secret rotation hashes tokens encrypts secrets and flashes each only once', function () {
    $owner = User::factory()->create();
    $organization = workspaceTenant($owner, 'acme');
    $project = $organization->projects()->create(['name' => 'API', 'slug' => 'api']);
    $url = '/organization/acme/project/api';
    $response = $this->actingAs($owner)->from($url)->post($url.'/rotate-token')->assertRedirect($url);
    $token = session('api_token');
    expect(strlen($token))->toBe(64);
    expect($project->fresh()->getRawOriginal('api_token'))->toBe(hash('sha256', $token));
    $this->get($url)->assertInertia(fn (Assert $page) => $page->component('project/Show', false)->where('flash.api_token', $token)->missing('project.api_token')->missing('project.github_secret'));
    $this->get($url)->assertInertia(fn (Assert $page) => $page->where('flash.api_token', null));
    $this->from($url)->post($url.'/rotate-token');
    expect($project->fresh()->getRawOriginal('api_token'))->not->toBe(hash('sha256', $token));
    $this->from($url)->post($url.'/rotate-github-secret');
    $secret = session('github_secret');
    expect($project->fresh()->github_secret)->toBe($secret);
    expect(DB::table('projects')->where('id', $project->id)->value('github_secret'))->not->toBe($secret);
    $this->get($url)->assertInertia(fn (Assert $page) => $page->where('flash.github_secret', $secret));
    $this->get($url)->assertInertia(fn (Assert $page) => $page->where('flash.github_secret', null));
});

test('rule and incident mutations cannot cross project boundaries', function () {
    $owner = User::factory()->create();
    $organization = workspaceTenant($owner, 'acme');
    $project = $organization->projects()->create(['name' => 'API', 'slug' => 'api']);
    $other = $organization->projects()->create(['name' => 'Other', 'slug' => 'other']);
    $url = '/organization/acme/project/api';
    $ruleData = ['name' => 'Errors', 'event_type' => 'payment.failed', 'threshold' => 5, 'window_seconds' => 300, 'severity' => 'high', 'enabled' => true];
    $this->actingAs($owner)->post($url.'/rules', $ruleData)->assertSessionHasNoErrors();
    $rule = $project->rules()->firstOrFail();
    $this->post($url.'/rules/'.$rule->id, [...$ruleData, 'threshold' => 10])->assertSessionHasNoErrors();
    expect($rule->fresh()->threshold)->toBe(10);
    $foreign = $other->rules()->create($ruleData);
    $this->post($url.'/rules/'.$foreign->id, $ruleData)->assertNotFound();
    $this->delete($url.'/rules/'.$foreign->id)->assertNotFound();
    $this->post($url.'/incidents', ['title' => 'Manual outage', 'severity' => 'high'])->assertSessionHasNoErrors();
    $incident = $project->incidents()->firstOrFail();
    $this->post($url.'/incidents/'.$incident->id.'/status', ['status' => 'resolved'])->assertSessionHasNoErrors();
    expect($incident->fresh()->resolved_at)->not->toBeNull();
    $this->post($url.'/incidents/'.$incident->id.'/status', ['status' => 'investigating'])->assertSessionHasNoErrors();
    expect($incident->fresh()->resolved_at)->toBeNull();
    $this->post('/organization/acme/project/other/incidents/'.$incident->id.'/status', ['status' => 'resolved'])->assertNotFound();
    $this->post($url.'/incidents/'.$incident->id.'/status', ['status' => 'invalid'])->assertSessionHasErrors('status');
    $this->delete($url.'/rules/'.$rule->id)->assertSessionHasNoErrors();
    expect($project->rules()->count())->toBe(0);
});
