<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Incident\Models\Incident;
use Modules\Incident\Models\OperationalLossEvent;
use Modules\Incident\Services\IncidentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

function makeLossUser(string $role = 'compliance_officer'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeLossIncident(array $overrides = []): Incident
{
    $reporter = makeLossUser('compliance_officer');

    return app(IncidentService::class)->create(array_merge([
        'title' => 'Loss Register Test Incident',
        'description' => 'For loss register tests.',
        'category' => 'operational',
        'severity' => 'high',
        'detected_at' => now()->toDateTimeString(),
        'is_data_breach' => false,
        'is_cyber_incident' => false,
        'affects_customers' => false,
    ], $overrides), $reporter->id);
}

// ---------------------------------------------------------------------------
// recordLoss creates and replaces
// ---------------------------------------------------------------------------

it('recordLoss creates an OperationalLossEvent for the incident', function () {
    $incident = makeLossIncident();
    $service = app(IncidentService::class);

    $loss = $service->recordLoss($incident, [
        'basel_category' => 'external_fraud',
        'gross_loss' => 5000000.00,
        'recovery_amount' => 1000000.00,
        'event_date' => now()->toDateString(),
        'recognized_date' => now()->toDateString(),
    ]);

    expect($loss)->toBeInstanceOf(OperationalLossEvent::class);
    expect((float) $loss->gross_loss)->toBe(5000000.00);
    expect((float) $loss->recovery_amount)->toBe(1000000.00);
    expect($loss->basel_category)->toBe('external_fraud');
});

it('recordLoss replaces existing event (unique per incident)', function () {
    $incident = makeLossIncident();
    $service = app(IncidentService::class);

    $service->recordLoss($incident, [
        'basel_category' => 'external_fraud',
        'gross_loss' => 5000000.00,
        'recovery_amount' => 0,
        'event_date' => now()->toDateString(),
        'recognized_date' => now()->toDateString(),
    ]);

    // Update with new values
    $updated = $service->recordLoss($incident, [
        'basel_category' => 'internal_fraud',
        'gross_loss' => 7500000.00,
        'recovery_amount' => 500000.00,
        'event_date' => now()->toDateString(),
        'recognized_date' => now()->toDateString(),
    ]);

    $total = OperationalLossEvent::where('incident_id', $incident->id)->count();
    expect($total)->toBe(1);
    expect((float) $updated->gross_loss)->toBe(7500000.00);
});

it('netLoss is correctly computed as gross_loss - recovery_amount', function () {
    $incident = makeLossIncident();
    $service = app(IncidentService::class);

    $loss = $service->recordLoss($incident, [
        'basel_category' => 'business_disruption',
        'gross_loss' => 3000000.00,
        'recovery_amount' => 800000.00,
        'event_date' => now()->toDateString(),
        'recognized_date' => now()->toDateString(),
    ]);

    expect($loss->netLoss())->toBe('2,200,000.00');
});

it('default currency is NGN when not specified', function () {
    $incident = makeLossIncident();
    $service = app(IncidentService::class);

    $loss = $service->recordLoss($incident, [
        'basel_category' => 'external_fraud',
        'gross_loss' => 1000000.00,
        'recovery_amount' => 0,
        'event_date' => now()->toDateString(),
        'recognized_date' => now()->toDateString(),
    ]);

    expect($loss->net_loss_currency)->toBe('NGN');
});

// ---------------------------------------------------------------------------
// HTTP endpoint
// ---------------------------------------------------------------------------

it('risk_owner can record a loss event via POST endpoint', function () {
    $user = makeLossUser('risk_owner');
    $incident = makeLossIncident();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/loss", [
            'basel_category' => 'external_fraud',
            'gross_loss' => 2500000.00,
            'recovery_amount' => 0,
            'event_date' => now()->toDateString(),
            'recognized_date' => now()->toDateString(),
        ])
        ->assertRedirect();

    expect(OperationalLossEvent::where('incident_id', $incident->id)->exists())->toBeTrue();
});

it('control_tester cannot record loss event', function () {
    $tester = makeLossUser('control_tester');
    $incident = makeLossIncident();

    $this->actingAs($tester)
        ->post("/incidents/{$incident->id}/loss", [
            'basel_category' => 'external_fraud',
            'gross_loss' => 100000.00,
            'recovery_amount' => 0,
            'event_date' => now()->toDateString(),
            'recognized_date' => now()->toDateString(),
        ])
        ->assertForbidden();
});

it('policy_owner cannot record loss event', function () {
    $policyOwner = makeLossUser('policy_owner');
    $incident = makeLossIncident();

    $this->actingAs($policyOwner)
        ->post("/incidents/{$incident->id}/loss", [
            'basel_category' => 'internal_fraud',
            'gross_loss' => 100000.00,
            'recovery_amount' => 0,
            'event_date' => now()->toDateString(),
            'recognized_date' => now()->toDateString(),
        ])
        ->assertForbidden();
});

it('auditor cannot record loss event', function () {
    $auditor = makeLossUser('auditor');
    $incident = makeLossIncident();

    $this->actingAs($auditor)
        ->post("/incidents/{$incident->id}/loss", [
            'basel_category' => 'internal_fraud',
            'gross_loss' => 100000.00,
            'recovery_amount' => 0,
            'event_date' => now()->toDateString(),
            'recognized_date' => now()->toDateString(),
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

it('missing gross_loss returns validation error', function () {
    $user = makeLossUser('compliance_officer');
    $incident = makeLossIncident();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/loss", [
            'basel_category' => 'external_fraud',
            'event_date' => now()->toDateString(),
            'recognized_date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('gross_loss');
});

it('invalid basel_category returns validation error', function () {
    $user = makeLossUser('compliance_officer');
    $incident = makeLossIncident();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/loss", [
            'basel_category' => 'not_a_real_category',
            'gross_loss' => 1000.00,
            'event_date' => now()->toDateString(),
            'recognized_date' => now()->toDateString(),
        ])
        ->assertSessionHasErrors('basel_category');
});
