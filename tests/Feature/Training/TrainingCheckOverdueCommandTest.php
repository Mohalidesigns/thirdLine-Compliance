<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\Certification;
use Modules\Training\Models\Training;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\TrainingService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeCommandTraining(array $overrides = []): Training
{
    return Training::create(array_merge([
        'code' => 'TRN-CMD-'.uniqid(),
        'title' => 'Command Test Training',
        'category' => 'aml',
        'is_mandatory' => false,
        'sla_days' => 30,
        'source' => 'native',
    ], $overrides));
}

function makeCommandUser(string $role = 'control_tester'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// ─── Overdue detection ────────────────────────────────────────────────────────

it('training:check-overdue marks enrolled enrollments past due_at as overdue', function () {
    $service = app(TrainingService::class);
    $training = makeCommandTraining();
    $user = makeCommandUser();

    // Create an enrollment that is already past due.
    $enrollment = TrainingEnrollment::create([
        'training_id' => $training->id,
        'user_id' => $user->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(10), // past due
        'status' => 'enrolled',
    ]);

    $this->artisan('training:check-overdue')->assertExitCode(0);

    expect($enrollment->fresh()->status)->toBe('overdue');
});

it('training:check-overdue marks in_progress enrollments past due_at as overdue', function () {
    $training = makeCommandTraining();
    $user = makeCommandUser();

    $enrollment = TrainingEnrollment::create([
        'training_id' => $training->id,
        'user_id' => $user->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(5),
        'started_at' => now()->subDays(20),
        'status' => 'in_progress',
    ]);

    $this->artisan('training:check-overdue')->assertExitCode(0);

    expect($enrollment->fresh()->status)->toBe('overdue');
});

it('training:check-overdue does not affect completed enrollments', function () {
    $training = makeCommandTraining();
    $user = makeCommandUser();

    // Completed enrollment that is past due_at — should remain completed.
    $enrollment = TrainingEnrollment::create([
        'training_id' => $training->id,
        'user_id' => $user->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(10),
        'completed_at' => now()->subDays(15),
        'status' => 'completed',
    ]);

    $this->artisan('training:check-overdue')->assertExitCode(0);

    expect($enrollment->fresh()->status)->toBe('completed');
});

it('training:check-overdue does not affect exempted enrollments', function () {
    $training = makeCommandTraining();
    $user = makeCommandUser();

    $enrollment = TrainingEnrollment::create([
        'training_id' => $training->id,
        'user_id' => $user->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(10),
        'exempted_at' => now()->subDays(20),
        'status' => 'exempted',
    ]);

    $this->artisan('training:check-overdue')->assertExitCode(0);

    expect($enrollment->fresh()->status)->toBe('exempted');
});

// ─── Audit events ─────────────────────────────────────────────────────────────

it('training:check-overdue writes audit events for each enrollment marked overdue', function () {
    $training = makeCommandTraining();
    $user1 = makeCommandUser();
    $user2 = makeCommandUser();

    TrainingEnrollment::create([
        'training_id' => $training->id,
        'user_id' => $user1->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(5),
        'status' => 'enrolled',
    ]);

    TrainingEnrollment::create([
        'training_id' => $training->id,
        'user_id' => $user2->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(3),
        'status' => 'enrolled',
    ]);

    $auditBefore = DB::table('audit_events')
        ->where('action', 'training_enrollment.updated')
        ->count();

    $this->artisan('training:check-overdue')->assertExitCode(0);

    $auditAfter = DB::table('audit_events')
        ->where('action', 'training_enrollment.updated')
        ->count();

    // Two enrollments should have been updated (marked overdue).
    expect($auditAfter - $auditBefore)->toBe(2);
});

// ─── Per-tenant scoping ───────────────────────────────────────────────────────

it('training:check-overdue only processes tenant 1 and does not affect tenant-2 enrollments when users are single-tenant', function () {
    // The resolveTenantIds() method discovers tenants from users.tenant_id (MVP: falls back to [1]).
    // Tenant 1 enrollment — should be marked overdue.
    $training1 = makeCommandTraining();
    $user1 = makeCommandUser();

    $t1Enrollment = TrainingEnrollment::create([
        'training_id' => $training1->id,
        'user_id' => $user1->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(5),
        'status' => 'enrolled',
        'tenant_id' => 1,
    ]);

    // Tenant 2 enrollment — inserted directly (bypasses global scope).
    // A different training to avoid the unique(training_id, user_id) constraint.
    $training2 = Training::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'code' => 'TRN-T2-'.uniqid(),
        'title' => 'Tenant 2 Training',
        'category' => 'general',
        'is_mandatory' => false,
        'sla_days' => 30,
        'source' => 'native',
    ]);

    $user2 = makeCommandUser();

    $t2Enrollment = TrainingEnrollment::withoutGlobalScopes()->create([
        'tenant_id' => 2,
        'training_id' => $training2->id,
        'user_id' => $user2->id,
        'enrolled_at' => now()->subDays(40),
        'due_at' => now()->subDays(5),
        'status' => 'enrolled',
    ]);

    $this->artisan('training:check-overdue')->assertExitCode(0);

    // Tenant 1 overdue enrollment should be flipped.
    expect($t1Enrollment->fresh()->status)->toBe('overdue');

    // Tenant 2 enrollment is not processed (no user with tenant_id=2 in users table,
    // so resolveTenantIds returns [1] only — matching the CCM command's MVP behavior).
    expect($t2Enrollment->fresh()->status)->toBe('enrolled');
});

// ─── Certification scan ───────────────────────────────────────────────────────

it('training:check-overdue also runs certification expiry scan', function () {
    $user = makeCommandUser('compliance_officer');

    $cert = Certification::create([
        'user_id' => $user->id,
        'name' => 'ACAMS',
        'issuing_body' => 'ACAMS Global',
        'issued_at' => now()->subYears(3)->toDateString(),
        'expires_at' => now()->addDays(30)->toDateString(), // expiring
        'status' => 'active', // currently wrong status
    ]);

    $this->artisan('training:check-overdue')->assertExitCode(0);

    expect($cert->fresh()->status)->toBe('expiring');
});
