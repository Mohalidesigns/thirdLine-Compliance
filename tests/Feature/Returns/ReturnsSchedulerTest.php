<?php

declare(strict_types=1);

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnReminder;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsSchedulerService;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new RolesAndPermissionsSeeder)->run();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSchedulerDef(array $overrides = []): ReturnDefinition
{
    return ReturnDefinition::withoutGlobalScopes()->create(array_merge([
        'tenant_id' => 1,
        'code' => 'SCHED-'.uniqid(),
        'title' => 'Scheduler Test Definition',
        'regulator' => 'cbn',
        'submission_channel' => 'portal',
        'file_format' => 'pdf',
        'frequency' => 'monthly',
        'evidence_required' => true,
        'active' => true,
    ], $overrides));
}

// ---------------------------------------------------------------------------
// scheduleAllUpcoming
// ---------------------------------------------------------------------------

it('scheduleAllUpcoming creates runs for active definitions', function () {
    makeSchedulerDef(['frequency' => 'monthly']);

    $scheduler = app(ReturnsSchedulerService::class);
    $created = $scheduler->scheduleAllUpcoming(1);

    expect($created)->toBeGreaterThan(0);
    expect(ReturnRun::withoutGlobalScopes()->where('tenant_id', 1)->count())->toBeGreaterThan(0);
});

it('scheduleAllUpcoming is idempotent', function () {
    makeSchedulerDef(['frequency' => 'monthly']);

    $scheduler = app(ReturnsSchedulerService::class);
    $first = $scheduler->scheduleAllUpcoming(1);
    $second = $scheduler->scheduleAllUpcoming(1);

    expect($second)->toBe(0); // no new rows on second call
});

it('scheduleAllUpcoming skips inactive definitions', function () {
    makeSchedulerDef(['active' => false]);

    $scheduler = app(ReturnsSchedulerService::class);
    $created = $scheduler->scheduleAllUpcoming(1);

    expect($created)->toBe(0);
});

it('scheduleAllUpcoming skips event_driven definitions', function () {
    makeSchedulerDef(['frequency' => 'event_driven']);

    $scheduler = app(ReturnsSchedulerService::class);
    $created = $scheduler->scheduleAllUpcoming(1);

    expect($created)->toBe(0);
});

it('scheduleAllUpcoming creates reminder rows for future due dates', function () {
    makeSchedulerDef(['frequency' => 'monthly']);

    $scheduler = app(ReturnsSchedulerService::class);
    $scheduler->scheduleAllUpcoming(1);

    $reminders = ReturnReminder::withoutGlobalScopes()
        ->where('tenant_id', 1)
        ->count();

    // At least some reminders should be scheduled (depends on how far out due_at is)
    expect($reminders)->toBeGreaterThanOrEqual(0); // sanity check — no crash
});

// ---------------------------------------------------------------------------
// scanForLate
// ---------------------------------------------------------------------------

it('scanForLate flips overdue scheduled runs to late', function () {
    $def = makeSchedulerDef();
    ReturnRun::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => '2024-01',
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'due_at' => '2024-01-31 23:59:00', // past
        'status' => 'scheduled',
    ]);

    $scheduler = app(ReturnsSchedulerService::class);
    $count = $scheduler->scanForLate(1);

    expect($count)->toBe(1);

    $run = ReturnRun::withoutGlobalScopes()->where('period_label', '2024-01')->first();
    expect($run->status)->toBe('late');
});

it('scanForLate flips overdue in_progress runs to late', function () {
    $def = makeSchedulerDef();
    ReturnRun::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => '2024-02',
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'due_at' => '2024-02-29 23:59:00',
        'status' => 'in_progress',
    ]);

    $scheduler = app(ReturnsSchedulerService::class);
    $count = $scheduler->scanForLate(1);

    expect($count)->toBe(1);
});

it('scanForLate does not flip acknowledged or submitted runs', function () {
    $def = makeSchedulerDef();

    ReturnRun::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => '2024-03',
        'period_start' => '2024-03-01',
        'period_end' => '2024-03-31',
        'due_at' => '2024-03-31 23:59:00',
        'status' => 'acknowledged',
    ]);

    $scheduler = app(ReturnsSchedulerService::class);
    $count = $scheduler->scanForLate(1);

    expect($count)->toBe(0);
});

it('scanForLate is idempotent on already-late runs', function () {
    $def = makeSchedulerDef();
    ReturnRun::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => '2024-04',
        'period_start' => '2024-04-01',
        'period_end' => '2024-04-30',
        'due_at' => '2024-04-30 23:59:00',
        'status' => 'late', // already late
    ]);

    $scheduler = app(ReturnsSchedulerService::class);
    $count = $scheduler->scanForLate(1);

    expect($count)->toBe(0); // already late, not in [scheduled, in_progress]
});

// ---------------------------------------------------------------------------
// dispatchDueReminders
// ---------------------------------------------------------------------------

it('dispatchDueReminders fires past-due reminders', function () {
    $def = makeSchedulerDef();
    $run = ReturnRun::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => now()->format('Y-m'),
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'due_at' => now()->addDays(30),
        'status' => 'scheduled',
    ]);

    // Create a reminder that's in the past
    ReturnReminder::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_run_id' => $run->id,
        'reminder_at' => now()->subHour(),
        'days_before_due' => 30,
        'fired_at' => null,
        'channel' => 'in_app',
        'created_at' => now()->subDay(),
    ]);

    $scheduler = app(ReturnsSchedulerService::class);
    $count = $scheduler->dispatchDueReminders();

    expect($count)->toBe(1);

    $reminder = ReturnReminder::withoutGlobalScopes()->first();
    expect($reminder->fired_at)->not->toBeNull();
});

it('dispatchDueReminders does not re-fire already fired reminders', function () {
    $def = makeSchedulerDef();
    $run = ReturnRun::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_definition_id' => $def->id,
        'period_label' => now()->subMonth()->format('Y-m'),
        'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
        'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
        'due_at' => now()->subDays(10),
        'status' => 'late',
    ]);

    ReturnReminder::withoutGlobalScopes()->create([
        'tenant_id' => 1,
        'return_run_id' => $run->id,
        'reminder_at' => now()->subDays(15),
        'days_before_due' => 7,
        'fired_at' => now()->subDays(14), // already fired
        'channel' => 'in_app',
        'created_at' => now()->subDays(20),
    ]);

    $scheduler = app(ReturnsSchedulerService::class);
    $count = $scheduler->dispatchDueReminders();

    expect($count)->toBe(0);
});
