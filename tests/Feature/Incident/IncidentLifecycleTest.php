<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Incident\Models\Incident;
use Modules\Incident\Services\IncidentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers — all prefixed "makeIncident*" to avoid global name collisions
// ---------------------------------------------------------------------------

function makeIncidentUser(string $role = 'compliance_officer'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeIncidentModel(array $overrides = []): Incident
{
    $service = app(IncidentService::class);
    $reporter = makeIncidentUser('compliance_officer');

    return $service->create(array_merge([
        'title' => 'Test Incident',
        'description' => 'An incident created for testing purposes.',
        'category' => 'operational',
        'severity' => 'medium',
        'detected_at' => now()->toDateTimeString(),
        'is_data_breach' => false,
        'is_cyber_incident' => false,
        'affects_customers' => false,
    ], $overrides), $reporter->id);
}

// ---------------------------------------------------------------------------
// Code generation
// ---------------------------------------------------------------------------

it('generates INC-YYYY-NNNN code on creation', function () {
    $incident = makeIncidentModel();

    $year = now()->year;
    expect($incident->code)->toMatch("/^INC-{$year}-\d{4}$/");
});

it('increments code sequence within the same year', function () {
    $i1 = makeIncidentModel();
    $i2 = makeIncidentModel();

    $seq1 = (int) substr($i1->code, -4);
    $seq2 = (int) substr($i2->code, -4);

    expect($seq2)->toBe($seq1 + 1);
});

// ---------------------------------------------------------------------------
// State machine — happy-path transitions
// ---------------------------------------------------------------------------

it('can transition detected -> triaged', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $user = makeIncidentUser();

    expect($incident->status::$name)->toBe('detected');

    $updated = $service->transition($incident, 'triaged', $user->id);

    expect($updated->status::$name)->toBe('triaged');
});

it('can walk full lifecycle to resolved', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    $service->transition($incident, 'triaged', $userId);
    $service->transition($incident->fresh(), 'investigating', $userId);
    $service->transition($incident->fresh(), 'remediation', $userId);
    $incident = $service->transition($incident->fresh(), 'resolved', $userId);

    expect($incident->status::$name)->toBe('resolved');
});

// ---------------------------------------------------------------------------
// Close guard — requires evidence
// ---------------------------------------------------------------------------

it('transition to closed fails with zero evidence', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    $service->transition($incident, 'triaged', $userId);
    $service->transition($incident->fresh(), 'investigating', $userId);
    $service->transition($incident->fresh(), 'remediation', $userId);
    $service->transition($incident->fresh(), 'resolved', $userId);

    expect(fn () => $service->transition($incident->fresh(), 'closed', $userId))
        ->toThrow(RuntimeException::class, 'at least one evidence item is required');
});

it('transition to closed succeeds with at least one evidence item', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    $service->transition($incident, 'triaged', $userId);
    $service->transition($incident->fresh(), 'investigating', $userId);
    $service->transition($incident->fresh(), 'remediation', $userId);
    $service->transition($incident->fresh(), 'resolved', $userId);

    // Attach evidence
    $service->attachEvidence($incident->fresh(), [
        'type' => 'document',
        'title' => 'Closure report',
        'description' => null,
    ], $userId);

    $closed = $service->transition($incident->fresh(), 'closed', $userId);

    expect($closed->status::$name)->toBe('closed');
    expect($closed->closed_at)->not->toBeNull();
    expect($closed->closed_by)->toBe($userId);
});

it('closeIncident helper also enforces evidence guard', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    $service->transition($incident, 'triaged', $userId);
    $service->transition($incident->fresh(), 'investigating', $userId);
    $service->transition($incident->fresh(), 'remediation', $userId);
    $service->transition($incident->fresh(), 'resolved', $userId);

    expect(fn () => $service->closeIncident($incident->fresh(), $userId))
        ->toThrow(RuntimeException::class);
});

// ---------------------------------------------------------------------------
// Invalid state
// ---------------------------------------------------------------------------

it('throws InvalidArgumentException for unknown target state', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    expect(fn () => $service->transition($incident, 'nonexistent', $userId))
        ->toThrow(InvalidArgumentException::class);
});

it('transition throws TransitionNotFound for illegal jump e.g. detected -> closed', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    expect(fn () => $service->transition($incident, 'closed', $userId))
        ->toThrow(Exception::class);
});

// ---------------------------------------------------------------------------
// HTTP route RBAC
// ---------------------------------------------------------------------------

it('unauthenticated user cannot list incidents', function () {
    $this->get('/incidents')->assertRedirect('/login');
});

it('auditor can view incidents index', function () {
    $this->actingAs(makeIncidentUser('auditor'))
        ->get('/incidents')
        ->assertOk();
});

it('auditor cannot create an incident', function () {
    $this->actingAs(makeIncidentUser('auditor'))
        ->post('/incidents', [
            'title' => 'Unauthorized',
            'description' => 'x',
            'category' => 'operational',
            'severity' => 'low',
            'detected_at' => now()->toDateTimeString(),
        ])
        ->assertForbidden();
});

it('compliance_officer can create an incident via POST', function () {
    $user = makeIncidentUser('compliance_officer');

    $this->actingAs($user)
        ->post('/incidents', [
            'title' => 'HTTP Create Test',
            'description' => 'Testing the create endpoint.',
            'category' => 'operational',
            'severity' => 'low',
            'detected_at' => now()->toDateTimeString(),
            'is_data_breach' => false,
            'is_cyber_incident' => false,
            'affects_customers' => false,
        ])
        ->assertRedirect();

    expect(Incident::where('title', 'HTTP Create Test')->exists())->toBeTrue();
});

it('transition endpoint returns 422 when evidence guard fails for close', function () {
    $user = makeIncidentUser('compliance_officer');
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);

    $service->transition($incident, 'triaged', $user->id);
    $service->transition($incident->fresh(), 'investigating', $user->id);
    $service->transition($incident->fresh(), 'remediation', $user->id);
    $service->transition($incident->fresh(), 'resolved', $user->id);

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/transition", ['to' => 'closed'])
        ->assertSessionHasErrors('transition');
});

it('policy_owner can create but not update an incident', function () {
    $policyOwner = makeIncidentUser('policy_owner');
    $incident = makeIncidentModel();

    // Create is allowed
    $this->actingAs($policyOwner)
        ->post('/incidents', [
            'title' => 'Policy Owner Report',
            'description' => 'Reported by policy owner.',
            'category' => 'conduct',
            'severity' => 'low',
            'detected_at' => now()->toDateTimeString(),
            'is_data_breach' => false,
            'is_cyber_incident' => false,
            'affects_customers' => false,
        ])
        ->assertRedirect();

    // Update is forbidden
    $this->actingAs($policyOwner)
        ->put("/incidents/{$incident->id}", ['title' => 'Changed'])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Audit events
// ---------------------------------------------------------------------------

it('state_transitioned audit event is written', function () {
    $incident = makeIncidentModel();
    $service = app(IncidentService::class);
    $userId = makeIncidentUser()->id;

    $service->transition($incident, 'triaged', $userId);

    $auditEntry = DB::table('audit_events')
        ->where('action', 'incident.state_transitioned')
        ->where('subject_id', $incident->id)
        ->first();

    expect($auditEntry)->not->toBeNull();
    $context = json_decode($auditEntry->context, true);
    expect($context['before']['status'])->toBe('detected');
    expect($context['after']['status'])->toBe('triaged');
});
