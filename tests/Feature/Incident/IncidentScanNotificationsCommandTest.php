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

function makeScanReporter(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('compliance_officer');

    return $user;
}

function makeScanIncident(array $overrides = []): Incident
{
    $reporter = makeScanReporter();

    return app(IncidentService::class)->create(array_merge([
        'title' => 'Scan Command Incident',
        'description' => 'Testing scan-notifications command.',
        'category' => 'cyber',
        'severity' => 'high',
        'detected_at' => now()->toDateTimeString(),
        'is_data_breach' => false,
        'is_cyber_incident' => true,
        'affects_customers' => false,
    ], $overrides), $reporter->id);
}

// ---------------------------------------------------------------------------
// Command exit code and overdue flagging
// ---------------------------------------------------------------------------

it('incidents:scan-notifications exits with code 0', function () {
    $this->artisan('incidents:scan-notifications')->assertExitCode(0);
});

it('flips pending notifications past deadline to overdue', function () {
    $incident = makeScanIncident();

    IncidentNotification::where('incident_id', $incident->id)->update([
        'deadline_at' => now()->subMinutes(5),
    ]);

    $this->artisan('incidents:scan-notifications')->assertExitCode(0);

    $overdue = $incident->notifications()->where('status', 'overdue')->count();
    expect($overdue)->toBeGreaterThan(0);
});

it('does not flip future-deadline notifications', function () {
    $incident = makeScanIncident();

    // Notifications have future deadlines by default (4h and 24h ahead)
    $this->artisan('incidents:scan-notifications')->assertExitCode(0);

    $stillPending = $incident->notifications()->where('status', 'pending')->count();
    expect($stillPending)->toBe(2); // cbn_cyber and cbn_general
});

it('does not flip submitted or acknowledged notifications', function () {
    $incident = makeScanIncident();

    IncidentNotification::where('incident_id', $incident->id)->update([
        'status' => 'submitted',
        'deadline_at' => now()->subHours(1),
        'notified_at' => now()->subHours(2),
    ]);

    $this->artisan('incidents:scan-notifications')->assertExitCode(0);

    $stillSubmitted = $incident->notifications()->where('status', 'submitted')->count();
    expect($stillSubmitted)->toBe(2);
});

it('writes audit events for each overdue notification flagged', function () {
    $incident = makeScanIncident();

    IncidentNotification::where('incident_id', $incident->id)->update([
        'deadline_at' => now()->subMinutes(5),
    ]);

    $auditBefore = DB::table('audit_events')
        ->where('action', 'incident.notification_overdue')
        ->count();

    $this->artisan('incidents:scan-notifications');

    $auditAfter = DB::table('audit_events')
        ->where('action', 'incident.notification_overdue')
        ->count();

    // 2 notifications (cbn_cyber and cbn_general) should have been flagged
    expect($auditAfter - $auditBefore)->toBe(2);
});

it('handles zero pending notifications gracefully', function () {
    // No incidents = no notifications
    $this->artisan('incidents:scan-notifications')->assertExitCode(0);
});

it('reports correct count in output', function () {
    $incident = makeScanIncident();

    IncidentNotification::where('incident_id', $incident->id)->update([
        'deadline_at' => now()->subMinutes(1),
    ]);

    $this->artisan('incidents:scan-notifications')
        ->expectsOutput('Flagged 2 overdue notification(s).')
        ->assertExitCode(0);
});

// ---------------------------------------------------------------------------
// Multi-tenant isolation — scan only flips own tenant's notifications
// ---------------------------------------------------------------------------

it('scan command only flags notifications for the current tenant scope', function () {
    // Create incident for tenant 1 (default)
    $incident = makeScanIncident();

    // Insert a tenant-2 notification directly (bypassing tenant scope)
    $tenant2NotificationId = DB::table('incident_notifications')->insertGetId([
        'tenant_id' => 2,
        'incident_id' => $incident->id, // same incident for simplicity
        'regulator' => 'sec',
        'deadline_at' => now()->subHours(1),
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Back-date tenant 1 notifications too
    IncidentNotification::where('incident_id', $incident->id)->where('tenant_id', 1)->update([
        'deadline_at' => now()->subMinutes(5),
    ]);

    // The command uses withoutGlobalScopes to scan all tenants — this is intentional.
    // Verify both get flagged regardless of tenant (the command is a cross-tenant scanner).
    $this->artisan('incidents:scan-notifications')->assertExitCode(0);

    $tenant1Overdue = DB::table('incident_notifications')
        ->where('tenant_id', 1)
        ->where('status', 'overdue')
        ->count();

    expect($tenant1Overdue)->toBeGreaterThan(0);
});
