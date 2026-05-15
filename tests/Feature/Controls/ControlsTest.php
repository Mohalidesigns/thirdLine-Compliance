<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Ccm\Rules\OverdueObligationsCcmRule;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\Issue;
use Modules\Controls\Services\ControlsService;
use Modules\Controls\Services\SampleSizeCalculator;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

/**
 * Returns a compliance_officer who can perform all control actions.
 */
function makeControlUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('compliance_officer');

    return $user;
}

function makeControl(array $overrides = []): Control
{
    return Control::create(array_merge([
        'title' => 'Daily suspense account reconciliation',
        'description' => 'Automated daily reconciliation.',
        'control_type' => 'preventive',
        'nature' => 'automated',
        'frequency' => 'daily',
        'owner_team' => 'Operations',
        'status' => 'active',
        'linked_obligation_ids' => [],
        'linked_risk_ids' => [],
    ], $overrides));
}

it('calculates sample size for small population (50 rows)', function () {
    $calc = app(SampleSizeCalculator::class);

    $result = $calc->calculate(50);
    expect($result)->toBeGreaterThan(0);
    expect($result)->toBeLessThanOrEqual(50);
    expect($result)->toBe(60 > 50 ? 50 : 60);
});

it('calculates sample size for medium population (200 rows)', function () {
    $calc = app(SampleSizeCalculator::class);

    $result = $calc->calculate(200);
    expect($result)->toBeGreaterThan(0);
    expect($result)->toBeLessThanOrEqual(200);
    expect($result)->toBe(60);
});

it('calculates sample size for large population (10000 rows)', function () {
    $calc = app(SampleSizeCalculator::class);

    $result = $calc->calculate(10000);
    expect($result)->toBeGreaterThan(60);
    expect($result)->toBeLessThanOrEqual(400);
});

it('records a test and updates next_test_due', function () {
    $user = makeControlUser();
    $control = makeControl(['frequency' => 'monthly']);

    $service = app(ControlsService::class);
    $test = $service->recordTest($control->id, [
        'outcome' => 'passed',
        'sample_size' => 30,
        'population_size' => 200,
        'tested_at' => now()->toDateTimeString(),
    ], $user->id);

    expect($test->outcome)->toBe('passed');

    $control->refresh();
    expect($control->last_tested_at)->not->toBeNull();
    expect($control->next_test_due)->not->toBeNull();
    $expected = now()->addMonth()->format('Y-m');
    expect($control->next_test_due->format('Y-m'))->toBe($expected);
});

it('creates an Issue automatically on failed test', function () {
    $user = makeControlUser();
    $control = makeControl(['frequency' => 'daily']);

    $issuesBefore = Issue::count();

    $service = app(ControlsService::class);
    $service->recordTest($control->id, [
        'outcome' => 'failed',
        'findings' => 'Control failed to detect all exceptions.',
        'tested_at' => now()->toDateTimeString(),
    ], $user->id);

    $issuesAfter = Issue::count();
    expect($issuesAfter - $issuesBefore)->toBe(1);

    $issue = Issue::orderByDesc('created_at')->first();
    expect($issue->source_type)->toBe('control_test');
    expect($issue->severity)->toBe('high');
    expect($issue->status)->toBe('open');
    expect($issue->linked_control_id)->toBe($control->id);
});

it('does NOT create an Issue on passed or partial test', function () {
    $user = makeControlUser();
    $control = makeControl();

    $service = app(ControlsService::class);
    $issuesBefore = Issue::count();

    $service->recordTest($control->id, [
        'outcome' => 'passed',
        'tested_at' => now()->toDateTimeString(),
    ], $user->id);

    $service->recordTest($control->id, [
        'outcome' => 'partial',
        'tested_at' => now()->toDateTimeString(),
    ], $user->id);

    expect(Issue::count() - $issuesBefore)->toBe(0);
});

function seedObligationForCcmTest(string $reference): void
{
    $regulatorId = DB::table('regulators')->insertGetId(['code' => 'T'.uniqid(), 'name' => 'Test Regulator', 'created_at' => now(), 'updated_at' => now()]);
    $typeId = DB::table('instrument_types')->insertGetId(['name' => 'TestType'.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $natureId = DB::table('natures')->insertGetId(['name' => 'TestNature'.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $statusId = DB::table('statuses')->insertGetId(['name' => 'TestStatus'.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $areaId = DB::table('areas_of_focus')->insertGetId(['name' => 'TestArea'.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
    $ratingId = DB::table('risk_ratings')->insertGetId(['name' => 'Low', 'created_at' => now(), 'updated_at' => now()]);

    $instrumentId = DB::table('instruments')->insertGetId([
        'tenant_id' => 1,
        'source_title' => 'Test Instrument',
        'regulator_id' => $regulatorId,
        'instrument_type_id' => $typeId,
        'nature_id' => $natureId,
        'status_id' => $statusId,
        'area_of_focus_id' => $areaId,
        'risk_rating_id' => $ratingId,
        'applicability' => 'Yes',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('obligations')->insert([
        'tenant_id' => 1,
        'instrument_id' => $instrumentId,
        'reference' => $reference,
        'title' => 'Test Overdue Obligation',
        'description' => 'An overdue obligation for testing',
        'next_due_date' => now()->subDays(5)->toDateString(),
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('OverdueObligationsCcmRule detects breach when obligations are past due', function () {
    seedObligationForCcmTest('OBL-TEST-001');

    $rule = new OverdueObligationsCcmRule;
    $result = $rule->evaluate();

    expect($result['metric_value'])->toBeGreaterThan(0.0);
    expect($result['breached'])->toBeTrue();
});

it('controls:run-ccm command writes ccm_rule_runs row and creates Issue on breach', function () {
    seedObligationForCcmTest('OBL-TEST-CCM');

    $runsBefore = DB::table('ccm_rule_runs')->count();
    $issuesBefore = Issue::count();

    $this->artisan('controls:run-ccm')->assertExitCode(0);

    expect(DB::table('ccm_rule_runs')->count())->toBeGreaterThan($runsBefore);
    expect(Issue::count())->toBeGreaterThan($issuesBefore);

    $breachedRun = DB::table('ccm_rule_runs')
        ->where('rule_name', 'overdue_obligations')
        ->where('status', 'threshold_breached')
        ->exists();

    expect($breachedRun)->toBeTrue();
});

// ─── Fix #2: destroy state guards ────────────────────────────────────────────

it('destroy succeeds when control is in draft state', function () {
    $user = makeControlUser();
    $control = makeControl(['status' => 'draft']);

    $this->actingAs($user)
        ->delete("/controls/{$control->id}")
        ->assertRedirect(route('controls.index'));

    expect(Control::withTrashed()->find($control->id)->trashed())->toBeTrue();
});

it('destroy returns 403 when control is active and record still exists', function () {
    $user = makeControlUser();
    $control = makeControl(['status' => 'active']);

    $this->actingAs($user)
        ->delete("/controls/{$control->id}")
        ->assertForbidden();

    expect(Control::find($control->id))->not->toBeNull();
});

it('destroy returns 403 when control is deprecated and record still exists', function () {
    $user = makeControlUser();
    $control = makeControl(['status' => 'deprecated']);

    $this->actingAs($user)
        ->delete("/controls/{$control->id}")
        ->assertForbidden();

    expect(Control::find($control->id))->not->toBeNull();
});

// ─── Fix #5: Issue status transition guards ───────────────────────────────────

it('valid issue status transition from open to in_progress succeeds', function () {
    $user = makeControlUser();
    $control = makeControl();
    $issue = Issue::create([
        'title' => 'Test Issue',
        'description' => 'Test',
        'source_type' => 'manual',
        'severity' => 'medium',
        'status' => 'open',
        'owner_team' => 'Ops',
        'linked_control_id' => $control->id,
    ]);

    $this->actingAs($user)
        ->patch("/issues/{$issue->id}", ['status' => 'in_progress'])
        ->assertRedirect();

    expect($issue->fresh()->status)->toBe('in_progress');
});

it('invalid issue status transition returns validation error', function () {
    $user = makeControlUser();
    $control = makeControl();
    $issue = Issue::create([
        'title' => 'Test Issue',
        'description' => 'Test',
        'source_type' => 'manual',
        'severity' => 'medium',
        'status' => 'open',
        'owner_team' => 'Ops',
        'linked_control_id' => $control->id,
    ]);

    $this->actingAs($user)
        ->patch("/issues/{$issue->id}", ['status' => 'closed'])
        ->assertSessionHasErrors('status');

    expect($issue->fresh()->status)->toBe('open');
});

it('transition to resolved requires non-empty resolution_notes', function () {
    $user = makeControlUser();
    $control = makeControl();
    $issue = Issue::create([
        'title' => 'Test Issue',
        'description' => 'Test',
        'source_type' => 'manual',
        'severity' => 'medium',
        'status' => 'in_progress',
        'owner_team' => 'Ops',
        'linked_control_id' => $control->id,
    ]);

    $this->actingAs($user)
        ->patch("/issues/{$issue->id}", ['status' => 'resolved'])
        ->assertSessionHasErrors('resolution_notes');

    expect($issue->fresh()->status)->toBe('in_progress');
});

it('transition to resolved succeeds with resolution_notes', function () {
    $user = makeControlUser();
    $control = makeControl();
    $issue = Issue::create([
        'title' => 'Test Issue',
        'description' => 'Test',
        'source_type' => 'manual',
        'severity' => 'medium',
        'status' => 'in_progress',
        'owner_team' => 'Ops',
        'linked_control_id' => $control->id,
    ]);

    $this->actingAs($user)
        ->patch("/issues/{$issue->id}", [
            'status' => 'resolved',
            'resolution_notes' => 'Fixed by patching the config.',
        ])
        ->assertRedirect();

    expect($issue->fresh()->status)->toBe('resolved');
});

it('emits one audit event per control test create', function () {
    $user = makeControlUser();
    $control = makeControl();

    $auditBefore = DB::table('audit_events')->where('action', 'control_test.created')->count();

    $service = app(ControlsService::class);
    $service->recordTest($control->id, [
        'outcome' => 'passed',
        'tested_at' => now()->toDateTimeString(),
    ], $user->id);

    $auditAfter = DB::table('audit_events')->where('action', 'control_test.created')->count();
    expect($auditAfter - $auditBefore)->toBe(1);
});

// ─── RBAC: wrong-role gets 403 ────────────────────────────────────────────────

it('control_tester cannot update a control', function () {
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');
    $control = makeControl();

    $this->actingAs($tester)
        ->put("/controls/{$control->id}", [
            'title' => 'Hacked title',
            'control_type' => 'preventive',
            'nature' => 'manual',
            'frequency' => 'daily',
            'owner_team' => 'Ops',
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('control_tester cannot create a control', function () {
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');

    $this->actingAs($tester)
        ->post('/controls', [
            'title' => 'Unauthorized control',
            'control_type' => 'preventive',
            'nature' => 'manual',
            'frequency' => 'daily',
            'owner_team' => 'Ops',
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('control_tester can record a test on an active control', function () {
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');
    $control = makeControl(['status' => 'active']);

    $this->actingAs($tester)
        ->post("/controls/{$control->id}/tests", [
            'outcome' => 'passed',
            'sample_size' => 10,
            'population_size' => 100,
            'tested_at' => now()->toDateTimeString(),
        ])
        ->assertRedirect();
});

it('risk_owner cannot create a control', function () {
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');

    $this->actingAs($riskOwner)
        ->post('/controls', [
            'title' => 'Unauthorized',
            'control_type' => 'preventive',
            'nature' => 'manual',
            'frequency' => 'daily',
            'owner_team' => 'Risk',
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('auditor can view controls list but cannot create', function () {
    $auditor = User::factory()->create(['email_verified_at' => now()]);
    $auditor->assignRole('auditor');
    makeControl();

    $this->actingAs($auditor)->get('/controls')->assertOk();
    $this->actingAs($auditor)->post('/controls', [
        'title' => 'Unauthorized',
        'control_type' => 'preventive',
        'nature' => 'manual',
        'frequency' => 'daily',
        'owner_team' => 'Audit',
        'status' => 'active',
    ])->assertForbidden();
});

it('control_tester can view issues list', function () {
    // control_tester HAS issues.view per the matrix.
    $tester = User::factory()->create(['email_verified_at' => now()]);
    $tester->assignRole('control_tester');
    makeControl();

    $this->actingAs($tester)
        ->get('/issues')
        ->assertOk();
});

it('risk_owner can view issues but cannot transition one', function () {
    // risk_owner has issues.view but NOT issues.transition.
    $riskOwner = User::factory()->create(['email_verified_at' => now()]);
    $riskOwner->assignRole('risk_owner');
    $control = makeControl();
    $issue = Issue::create([
        'title' => 'Issue',
        'description' => 'Desc',
        'source_type' => 'manual',
        'severity' => 'low',
        'status' => 'open',
        'owner_team' => 'Risk',
        'linked_control_id' => $control->id,
    ]);

    // View should work.
    $this->actingAs($riskOwner)->get("/issues/{$issue->id}")->assertOk();

    // Status transition should be forbidden (requires issues.transition).
    $this->actingAs($riskOwner)
        ->patch("/issues/{$issue->id}", ['status' => 'in_progress'])
        ->assertForbidden();
});
