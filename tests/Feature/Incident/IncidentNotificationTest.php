<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Incident\Models\Incident;
use Modules\Incident\Models\IncidentNotification;
use Modules\Incident\Services\IncidentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

function makeNotificationIncidentUser(string $role = 'compliance_officer'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function makeBaseIncident(array $overrides = []): Incident
{
    $reporter = makeNotificationIncidentUser('compliance_officer');

    return app(IncidentService::class)->create(array_merge([
        'title' => 'Base Incident',
        'description' => 'For notification tests.',
        'category' => 'operational',
        'severity' => 'medium',
        'detected_at' => now()->toDateTimeString(),
        'is_data_breach' => false,
        'is_cyber_incident' => false,
        'affects_customers' => false,
    ], $overrides), $reporter->id);
}

// ---------------------------------------------------------------------------
// Regulator notification scheduling
// ---------------------------------------------------------------------------

it('cyber incident creates cbn_cyber (4h) and cbn_general (24h) notifications', function () {
    $incident = makeBaseIncident([
        'is_cyber_incident' => true,
        'detected_at' => now()->toDateTimeString(),
    ]);

    $notifications = $incident->notifications()->get();

    expect($notifications->pluck('regulator')->toArray())
        ->toContain('cbn_cyber')
        ->toContain('cbn_general');

    $cbnCyber = $notifications->where('regulator', 'cbn_cyber')->first();
    $cbnGeneral = $notifications->where('regulator', 'cbn_general')->first();

    // 4h deadline — deadline_at is 240 minutes ahead of detected_at
    $cyberMinutes = (int) $incident->detected_at->diffInMinutes($cbnCyber->deadline_at);
    expect($cyberMinutes)->toBeBetween(239, 241);

    // 24h deadline — 1440 minutes
    $generalMinutes = (int) $incident->detected_at->diffInMinutes($cbnGeneral->deadline_at);
    expect($generalMinutes)->toBeBetween(1439, 1441);
});

it('data breach incident creates ndpc (72h) notification', function () {
    $incident = makeBaseIncident([
        'is_data_breach' => true,
        'detected_at' => now()->toDateTimeString(),
    ]);

    $notification = $incident->notifications()->where('regulator', 'ndpc')->first();

    expect($notification)->not->toBeNull();
    expect($notification->status)->toBe('pending');
    // 72h = 4320 minutes ahead of detected_at
    $ndpcMinutes = (int) $incident->detected_at->diffInMinutes($notification->deadline_at);
    expect($ndpcMinutes)->toBeBetween(4319, 4321);
});

it('non-cyber non-breach incident creates NO notifications', function () {
    $incident = makeBaseIncident([
        'is_cyber_incident' => false,
        'is_data_breach' => false,
    ]);

    expect($incident->notifications()->count())->toBe(0);
});

it('both flags set creates three notifications (cbn_cyber, cbn_general, ndpc)', function () {
    $incident = makeBaseIncident([
        'is_cyber_incident' => true,
        'is_data_breach' => true,
    ]);

    $regulators = $incident->notifications()->pluck('regulator')->toArray();

    expect($regulators)->toContain('cbn_cyber');
    expect($regulators)->toContain('cbn_general');
    expect($regulators)->toContain('ndpc');
    expect(count($regulators))->toBe(3);
});

it('scheduleRegulatorNotifications is idempotent — no duplicates on repeat call', function () {
    $incident = makeBaseIncident(['is_cyber_incident' => true]);
    $service = app(IncidentService::class);

    // Call again manually
    $service->scheduleRegulatorNotifications($incident);

    $cbnCyberCount = $incident->notifications()->where('regulator', 'cbn_cyber')->count();
    expect($cbnCyberCount)->toBe(1);
});

// ---------------------------------------------------------------------------
// Record notification submission flow
// ---------------------------------------------------------------------------

it('recordNotification marks status submitted and captures reference', function () {
    $incident = makeBaseIncident(['is_cyber_incident' => true]);
    $service = app(IncidentService::class);
    $userId = makeNotificationIncidentUser()->id;

    $notification = $incident->notifications()->where('regulator', 'cbn_cyber')->first();

    $updated = $service->recordNotification($notification, 'CBN-REF-001', $userId);

    expect($updated->status)->toBe('submitted');
    expect($updated->notification_reference)->toBe('CBN-REF-001');
    expect($updated->notified_at)->not->toBeNull();
});

it('record notification via HTTP endpoint marks submitted', function () {
    $user = makeNotificationIncidentUser('compliance_officer');
    $incident = makeBaseIncident(['is_cyber_incident' => true]);
    $notification = $incident->notifications()->where('regulator', 'cbn_cyber')->first();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/notify/{$notification->id}", [
            'reference' => 'CBN-HTTP-REF',
        ])
        ->assertRedirect();

    expect($notification->fresh()->status)->toBe('submitted');
    expect($notification->fresh()->notification_reference)->toBe('CBN-HTTP-REF');
});

it('user without incidents.notify cannot record notification', function () {
    $user = makeNotificationIncidentUser('policy_owner'); // policy_owner has no incidents.notify
    $incident = makeBaseIncident(['is_cyber_incident' => true]);
    $notification = $incident->notifications()->where('regulator', 'cbn_cyber')->first();

    $this->actingAs($user)
        ->post("/incidents/{$incident->id}/notify/{$notification->id}", [
            'reference' => 'SHOULD-FAIL',
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Overdue scanner
// ---------------------------------------------------------------------------

it('incidents:scan-notifications flips pending overdue notifications to overdue status', function () {
    $incident = makeBaseIncident(['is_cyber_incident' => true]);

    // Back-date the deadline so it's in the past
    IncidentNotification::where('incident_id', $incident->id)->update([
        'deadline_at' => now()->subHours(1),
    ]);

    $this->artisan('incidents:scan-notifications')->assertExitCode(0);

    $allOverdue = $incident->notifications()->where('status', 'overdue')->count();
    expect($allOverdue)->toBeGreaterThan(0);
});

it('incidents:scan-notifications does NOT flip submitted notifications', function () {
    $incident = makeBaseIncident(['is_cyber_incident' => true]);

    // Mark one as submitted and back-date
    $notification = $incident->notifications()->where('regulator', 'cbn_cyber')->first();
    $notification->update([
        'status' => 'submitted',
        'deadline_at' => now()->subHours(2),
        'notified_at' => now()->subHours(5),
    ]);

    $this->artisan('incidents:scan-notifications')->assertExitCode(0);

    expect($notification->fresh()->status)->toBe('submitted');
});

it('incidents:scan-notifications writes audit events for each overdue flagged', function () {
    $incident = makeBaseIncident(['is_cyber_incident' => true]);

    IncidentNotification::where('incident_id', $incident->id)->update([
        'deadline_at' => now()->subHours(1),
    ]);

    $auditBefore = DB::table('audit_events')
        ->where('action', 'incident.notification_overdue')
        ->count();

    $this->artisan('incidents:scan-notifications');

    $auditAfter = DB::table('audit_events')
        ->where('action', 'incident.notification_overdue')
        ->count();

    expect($auditAfter)->toBeGreaterThan($auditBefore);
});
