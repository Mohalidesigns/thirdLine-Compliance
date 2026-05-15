<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\Training;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\TrainingService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeTraining(array $overrides = []): Training
{
    return Training::create(array_merge([
        'code' => 'TRN-TEST-'.uniqid(),
        'title' => 'Test Training',
        'category' => 'aml',
        'is_mandatory' => true,
        'sla_days' => 30,
        'source' => 'native',
    ], $overrides));
}

function makeTrainingUser(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// ─── Enrollment lifecycle ─────────────────────────────────────────────────────

it('enrollUser creates an enrollment with correct due_at', function () {
    $service = app(TrainingService::class);
    $training = makeTraining(['sla_days' => 14]);
    $user = makeTrainingUser('control_tester');

    $enrollment = $service->enrollUser($training, $user);

    expect($enrollment->status)->toBe('enrolled');
    expect($enrollment->training_id)->toBe($training->id);
    expect($enrollment->user_id)->toBe($user->id);
    expect($enrollment->due_at->format('Y-m-d'))->toBe(now()->addDays(14)->format('Y-m-d'));
});

it('enrollUser is idempotent — returns existing enrollment on second call', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $user = makeTrainingUser('control_tester');

    $first = $service->enrollUser($training, $user);
    $second = $service->enrollUser($training, $user);

    expect($first->id)->toBe($second->id);
    expect(TrainingEnrollment::count())->toBe(1);
});

it('markStarted transitions enrollment from enrolled to in_progress', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $user = makeTrainingUser('control_tester');

    $enrollment = $service->enrollUser($training, $user);
    $service->markStarted($enrollment);

    expect($enrollment->fresh()->status)->toBe('in_progress');
    expect($enrollment->fresh()->started_at)->not->toBeNull();
});

it('markCompleted transitions enrollment to completed and records score', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $user = makeTrainingUser('control_tester');
    $admin = makeTrainingUser('compliance_officer');

    $enrollment = $service->enrollUser($training, $user);
    $service->markStarted($enrollment);
    $service->markCompleted($enrollment->fresh(), 85, $admin->id);

    $fresh = $enrollment->fresh();
    expect($fresh->status)->toBe('completed');
    expect($fresh->score)->toBe(85);
    expect($fresh->completed_at)->not->toBeNull();
    expect($fresh->completed_by)->toBe($admin->id);
});

it('markOverdue transitions an enrolled enrollment to overdue', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $user = makeTrainingUser('control_tester');

    $enrollment = $service->enrollUser($training, $user);
    $service->markOverdue($enrollment);

    expect($enrollment->fresh()->status)->toBe('overdue');
});

it('grantExemption transitions enrollment to exempted with reason', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $user = makeTrainingUser('control_tester');
    $admin = makeTrainingUser('compliance_officer');

    $enrollment = $service->enrollUser($training, $user);
    $service->grantExemption($enrollment, 'User is on extended leave.', $admin->id);

    $fresh = $enrollment->fresh();
    expect($fresh->status)->toBe('exempted');
    expect($fresh->exemption_reason)->toBe('User is on extended leave.');
    expect($fresh->exempted_at)->not->toBeNull();
});

// ─── RBAC ─────────────────────────────────────────────────────────────────────

it('control_tester can complete their own enrollment via POST', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $tester = makeTrainingUser('control_tester');

    $enrollment = $service->enrollUser($training, $tester);

    $this->actingAs($tester)
        ->post("/my/training/{$enrollment->id}/complete", ['score' => 90])
        ->assertRedirect(route('my.training.index'));

    expect($enrollment->fresh()->status)->toBe('completed');
});

it('control_tester cannot complete another users enrollment', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $tester = makeTrainingUser('control_tester');
    $other = makeTrainingUser('control_tester');

    $enrollment = $service->enrollUser($training, $other); // enrolled for "other"

    $this->actingAs($tester) // acting as "tester" — not the owner
        ->post("/my/training/{$enrollment->id}/complete", ['score' => 90])
        ->assertForbidden();
});

it('auditor can view training index but cannot complete an enrollment', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $auditor = makeTrainingUser('auditor');
    $tester = makeTrainingUser('control_tester');

    $enrollment = $service->enrollUser($training, $auditor);

    // auditor has training.view so the index page should work.
    $this->actingAs($auditor)
        ->get('/my/training')
        ->assertOk();

    // auditor does NOT have training.complete, so completing should be 403.
    $this->actingAs($auditor)
        ->post("/my/training/{$enrollment->id}/complete", ['score' => 70])
        ->assertForbidden();
});

it('risk_owner cannot view another users training enrollment', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $tester = makeTrainingUser('control_tester');
    $riskOwner = makeTrainingUser('risk_owner');

    $enrollment = $service->enrollUser($training, $tester);

    // risk_owner doesn't have training.manage, so viewing someone else's enrollment should 403.
    $this->actingAs($riskOwner)
        ->get("/my/training/{$enrollment->id}")
        ->assertForbidden();
});

// ─── Exemption flow ───────────────────────────────────────────────────────────

it('compliance_officer can complete an enrollment as admin override', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $tester = makeTrainingUser('control_tester');
    $officer = makeTrainingUser('compliance_officer');

    $enrollment = $service->enrollUser($training, $tester);

    // compliance_officer has training.manage so can complete any enrollment.
    $service->markCompleted($enrollment, 75, $officer->id);

    expect($enrollment->fresh()->status)->toBe('completed');
    expect($enrollment->fresh()->completed_by)->toBe($officer->id);
});

// ─── autoEnrollMandatoryForUser ───────────────────────────────────────────────

it('autoEnrollMandatoryForUser enrolls user in all mandatory trainings with matching target_roles', function () {
    $service = app(TrainingService::class);

    // Training with no target_roles — applies to all.
    $allRolesTraining = makeTraining(['is_mandatory' => true, 'target_roles' => null, 'code' => 'TRN-ALL']);

    // Training targeting compliance_officer only.
    $officerTraining = makeTraining([
        'is_mandatory' => true,
        'target_roles' => ['compliance_officer'],
        'code' => 'TRN-OFFICER',
    ]);

    // Training targeting risk_owner only.
    $riskOwnerTraining = makeTraining([
        'is_mandatory' => true,
        'target_roles' => ['risk_owner'],
        'code' => 'TRN-RISK',
    ]);

    $officer = makeTrainingUser('compliance_officer');

    $count = $service->autoEnrollMandatoryForUser($officer);

    // Officer should be enrolled in the "all roles" and "compliance_officer" trainings.
    expect($count)->toBe(2);

    expect(TrainingEnrollment::where('user_id', $officer->id)
        ->where('training_id', $allRolesTraining->id)->exists())->toBeTrue();

    expect(TrainingEnrollment::where('user_id', $officer->id)
        ->where('training_id', $officerTraining->id)->exists())->toBeTrue();

    // Should NOT be enrolled in the risk_owner-only training.
    expect(TrainingEnrollment::where('user_id', $officer->id)
        ->where('training_id', $riskOwnerTraining->id)->exists())->toBeFalse();
});

// ─── completionStats ─────────────────────────────────────────────────────────

it('completionStats returns correct mandatory_total and completion_rate', function () {
    $service = app(TrainingService::class);

    $training = makeTraining(['is_mandatory' => true]);
    $user1 = makeTrainingUser('control_tester');
    $user2 = makeTrainingUser('control_tester');

    $e1 = $service->enrollUser($training, $user1);
    $e2 = $service->enrollUser($training, $user2);

    $service->markCompleted($e1, 80, $user1->id);

    $stats = $service->completionStats(1);

    expect($stats['mandatory_total'])->toBe(2);
    expect($stats['mandatory_completed'])->toBe(1);
    expect($stats['completion_rate'])->toBe(0.5);
    expect($stats['by_category'])->toHaveKey('aml');
    expect($stats['by_role'])->toHaveKey('control_tester');
});

// ─── Audit events ─────────────────────────────────────────────────────────────

it('creates audit event when enrollment is created', function () {
    $service = app(TrainingService::class);
    $training = makeTraining();
    $user = makeTrainingUser('control_tester');

    $auditBefore = DB::table('audit_events')
        ->where('action', 'training_enrollment.created')
        ->count();

    $service->enrollUser($training, $user);

    $auditAfter = DB::table('audit_events')
        ->where('action', 'training_enrollment.created')
        ->count();

    expect($auditAfter - $auditBefore)->toBe(1);
});
