<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Ccm\Rules\OverdueObligationsCcmRule;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\ControlTest;
use Modules\Controls\Models\Issue;
use Modules\Controls\Services\ControlsService;
use Modules\Controls\Services\SampleSizeCalculator;

uses(RefreshDatabase::class);

function makeControlUser(): User
{
    return User::factory()->create();
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

it('OverdueObligationsCcmRule detects breach when obligations are past due', function () {
    DB::table('obligations')->insert([
        'tenant_id' => 1,
        'instrument_id' => 1,
        'reference' => 'OBL-TEST-001',
        'title' => 'Test Overdue Obligation',
        'next_due_date' => now()->subDays(5)->toDateString(),
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rule = new OverdueObligationsCcmRule;
    $result = $rule->evaluate();

    expect($result['metric_value'])->toBeGreaterThan(0.0);
    expect($result['breached'])->toBeTrue();
});

it('controls:run-ccm command writes ccm_rule_runs row and creates Issue on breach', function () {
    DB::table('obligations')->insert([
        'tenant_id' => 1,
        'instrument_id' => 1,
        'reference' => 'OBL-TEST-CCM',
        'title' => 'Overdue obligation for CCM test',
        'next_due_date' => now()->subDays(10)->toDateString(),
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

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
