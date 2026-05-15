<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Incident\Models\Incident;
use Modules\Incident\Models\IncidentEvidence;
use Modules\Incident\Services\IncidentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

function makeEvidenceUser(string $role = 'compliance_officer'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeIncidentForEvidence(array $overrides = []): Incident
{
    $reporter = makeEvidenceUser('compliance_officer');

    return app(IncidentService::class)->create(array_merge([
        'title' => 'Evidence Test Incident',
        'description' => 'For evidence tests.',
        'category' => 'operational',
        'severity' => 'low',
        'detected_at' => now()->toDateTimeString(),
        'is_data_breach' => false,
        'is_cyber_incident' => false,
        'affects_customers' => false,
    ], $overrides), $reporter->id);
}

// ---------------------------------------------------------------------------
// Observer: closure_evidence_count denorm
// ---------------------------------------------------------------------------

it('attaching evidence increments closure_evidence_count', function () {
    $incident = makeIncidentForEvidence();
    $service = app(IncidentService::class);
    $userId = makeEvidenceUser()->id;

    // DB default is 0 — read from fresh to avoid in-memory null
    expect($incident->fresh()->closure_evidence_count)->toBe(0);

    $service->attachEvidence($incident, [
        'type' => 'document',
        'title' => 'Test doc',
    ], $userId);

    expect($incident->fresh()->closure_evidence_count)->toBe(1);
});

it('attaching two evidence items increments count to 2', function () {
    $incident = makeIncidentForEvidence();
    $service = app(IncidentService::class);
    $userId = makeEvidenceUser()->id;

    $service->attachEvidence($incident, ['type' => 'document', 'title' => 'Doc 1'], $userId);
    $service->attachEvidence($incident, ['type' => 'screenshot', 'title' => 'Screenshot 1'], $userId);

    expect($incident->fresh()->closure_evidence_count)->toBe(2);
});

it('deleting evidence decrements closure_evidence_count', function () {
    $incident = makeIncidentForEvidence();
    $service = app(IncidentService::class);
    $userId = makeEvidenceUser()->id;

    $service->attachEvidence($incident, ['type' => 'document', 'title' => 'Doc A'], $userId);
    $service->attachEvidence($incident, ['type' => 'document', 'title' => 'Doc B'], $userId);

    expect($incident->fresh()->closure_evidence_count)->toBe(2);

    // Retrieve via model query so the observer can fire on delete
    IncidentEvidence::where('incident_id', $incident->id)->first()->delete();

    expect($incident->fresh()->closure_evidence_count)->toBe(1);
});

it('count does not drop below zero when all evidence deleted', function () {
    $incident = makeIncidentForEvidence();
    $service = app(IncidentService::class);
    $userId = makeEvidenceUser()->id;

    $service->attachEvidence($incident, ['type' => 'log', 'title' => 'Log'], $userId);

    // Delete each record individually so the observer fires
    foreach ($incident->evidence()->get() as $ev) {
        $ev->delete();
    }

    expect($incident->fresh()->closure_evidence_count)->toBe(0);
});

// ---------------------------------------------------------------------------
// HTTP attach evidence
// ---------------------------------------------------------------------------

it('compliance_officer can attach evidence via POST endpoint', function () {
    $user = makeEvidenceUser('compliance_officer');
    $incident = makeIncidentForEvidence();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/evidence", [
            'type' => 'document',
            'title' => 'HTTP Evidence Test',
        ])
        ->assertRedirect();

    expect($incident->fresh()->closure_evidence_count)->toBe(1);
    expect(IncidentEvidence::where('incident_id', $incident->id)->where('title', 'HTTP Evidence Test')->exists())->toBeTrue();
});

it('control_tester can attach evidence', function () {
    $tester = makeEvidenceUser('control_tester');
    $incident = makeIncidentForEvidence();

    $this->actingAs($tester)
        ->post("/incidents/{$incident->id}/evidence", [
            'type' => 'screenshot',
            'title' => 'Tester evidence',
        ])
        ->assertRedirect();

    expect($incident->fresh()->closure_evidence_count)->toBe(1);
});

it('policy_owner cannot attach evidence', function () {
    $policyOwner = makeEvidenceUser('policy_owner');
    $incident = makeIncidentForEvidence();

    $this->actingAs($policyOwner)
        ->post("/incidents/{$incident->id}/evidence", [
            'type' => 'document',
            'title' => 'Unauthorized',
        ])
        ->assertForbidden();

    expect($incident->fresh()->closure_evidence_count)->toBe(0);
});

it('auditor cannot attach evidence', function () {
    $auditor = makeEvidenceUser('auditor');
    $incident = makeIncidentForEvidence();

    $this->actingAs($auditor)
        ->post("/incidents/{$incident->id}/evidence", [
            'type' => 'document',
            'title' => 'Auditor attempt',
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

it('missing title returns validation error on evidence attach', function () {
    $user = makeEvidenceUser('compliance_officer');
    $incident = makeIncidentForEvidence();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/evidence", [
            'type' => 'document',
        ])
        ->assertSessionHasErrors('title');
});

it('invalid type returns validation error on evidence attach', function () {
    $user = makeEvidenceUser('compliance_officer');
    $incident = makeIncidentForEvidence();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/evidence", [
            'type' => 'video',
            'title' => 'Invalid type',
        ])
        ->assertSessionHasErrors('type');
});

// ---------------------------------------------------------------------------
// Evidence is immutable — no updated_at
// ---------------------------------------------------------------------------

it('evidence record has created_at but no updated_at column touched by service', function () {
    $incident = makeIncidentForEvidence();
    $service = app(IncidentService::class);
    $userId = makeEvidenceUser()->id;

    $evidence = $service->attachEvidence($incident, [
        'type' => 'email',
        'title' => 'Immutable evidence',
    ], $userId);

    expect($evidence->created_at)->not->toBeNull();
    // The model has $timestamps = false, so updated_at should not exist
    expect(array_key_exists('updated_at', $evidence->getAttributes()))->toBeFalse();
});
