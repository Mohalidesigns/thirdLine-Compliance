<?php

declare(strict_types=1);

namespace Modules\Returns\Services;

use App\Services\AuditWriter;
use Illuminate\Support\Carbon;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnReminder;
use Modules\Returns\Models\ReturnRun;

class ReturnsSchedulerService
{
    public function __construct(
        private readonly ReturnsService $returnsService,
        private readonly AuditWriter $auditWriter,
    ) {}

    /**
     * Ensure a ReturnRun exists for each active definition's current period
     * and the upcoming period. Idempotent — safe to run multiple times.
     *
     * @return int Number of new runs created
     */
    public function scheduleAllUpcoming(int $tenantId): int
    {
        $created = 0;

        $definitions = ReturnDefinition::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($definitions as $definition) {
            $periods = $this->computePeriods($definition->frequency);

            foreach ($periods as $period) {
                $run = $this->returnsService->createReturnRun(
                    $definition,
                    $period['label'],
                    $period['start'],
                    $period['end'],
                    $period['due_at'],
                );

                // createReturnRun is idempotent — if a new row was persisted,
                // wasRecentlyCreated will be true.
                if ($run->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Flip runs where due_at < now and status is scheduled or in_progress
     * to 'late'. Each flip is audited.
     *
     * @return int Number of runs flipped to late
     */
    public function scanForLate(int $tenantId): int
    {
        $overdue = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('due_at', '<', now())
            ->get();

        $count = 0;

        foreach ($overdue as $run) {
            $this->returnsService->markLate($run);
            $count++;
        }

        return $count;
    }

    /**
     * Find return_reminders where reminder_at < now and fired_at is null,
     * mark them fired, and write an audit event.
     *
     * Note: No actual email or push notification is sent in this MVP phase.
     * In-app visibility is provided by the reminder row itself (fired_at is null
     * = pending, not null = fired). Email delivery is out of scope for Phase 1.
     *
     * @return int Number of reminders fired
     */
    public function dispatchDueReminders(): int
    {
        $due = ReturnReminder::withoutGlobalScopes()
            ->whereNull('fired_at')
            ->where('reminder_at', '<', now())
            ->with('run:id,tenant_id,return_definition_id,period_label,due_at')
            ->get();

        $count = 0;

        foreach ($due as $reminder) {
            $reminder->update(['fired_at' => now()]);

            if ($reminder->run !== null) {
                $this->auditWriter->record(
                    action: 'return_reminder.fired',
                    subject: $reminder->run,
                    context: [
                        'reminder_id' => $reminder->id,
                        'days_before_due' => $reminder->days_before_due,
                        'reminder_at' => $reminder->reminder_at->toIso8601String(),
                        'channel' => $reminder->channel,
                    ],
                );
            }

            $count++;
        }

        return $count;
    }

    // ---------------------------------------------------------------------------
    // Period computation helpers
    // ---------------------------------------------------------------------------

    /**
     * Compute the current period and next period for a given frequency.
     * Returns an array of period descriptors with label, start, end, due_at.
     *
     * The due_at is conservatively set to the last day of the following
     * month after the period ends (a common regulatory convention).
     * For event_driven/ad_hoc frequencies, no automatic run is created
     * (these require manual creation).
     *
     * @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}>
     */
    private function computePeriods(string $frequency): array
    {
        $now = now();

        return match ($frequency) {
            'daily' => $this->dailyPeriods($now),
            'weekly' => $this->weeklyPeriods($now),
            'monthly' => $this->monthlyPeriods($now),
            'quarterly' => $this->quarterlyPeriods($now),
            'half_year' => $this->halfYearPeriods($now),
            'annual' => $this->annualPeriods($now),
            // event_driven and ad_hoc require manual run creation — no auto-scheduling
            default => [],
        };
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function dailyPeriods(Carbon $now): array
    {
        $today = $now->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        return [
            [
                'label' => $today->format('Y-m-d'),
                'start' => $today,
                'end' => $today->copy()->endOfDay(),
                'due_at' => $today->copy()->addDay()->setTime(23, 59, 0),
            ],
            [
                'label' => $tomorrow->format('Y-m-d'),
                'start' => $tomorrow,
                'end' => $tomorrow->copy()->endOfDay(),
                'due_at' => $tomorrow->copy()->addDay()->setTime(23, 59, 0),
            ],
        ];
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function weeklyPeriods(Carbon $now): array
    {
        $thisWeekStart = $now->copy()->startOfWeek();
        $nextWeekStart = $thisWeekStart->copy()->addWeek();

        return [
            [
                'label' => 'W'.$now->weekOfYear.'-'.$now->year,
                'start' => $thisWeekStart,
                'end' => $thisWeekStart->copy()->endOfWeek(),
                'due_at' => $thisWeekStart->copy()->addDays(7)->setTime(23, 59, 0),
            ],
            [
                'label' => 'W'.($now->weekOfYear + 1).'-'.$now->year,
                'start' => $nextWeekStart,
                'end' => $nextWeekStart->copy()->endOfWeek(),
                'due_at' => $nextWeekStart->copy()->addDays(7)->setTime(23, 59, 0),
            ],
        ];
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function monthlyPeriods(Carbon $now): array
    {
        $thisMonth = $now->copy()->startOfMonth();
        $nextMonth = $thisMonth->copy()->addMonth();

        return [
            [
                'label' => $thisMonth->format('Y-m'),
                'start' => $thisMonth,
                'end' => $thisMonth->copy()->endOfMonth(),
                'due_at' => $thisMonth->copy()->addMonth()->endOfMonth()->setTime(23, 59, 0),
            ],
            [
                'label' => $nextMonth->format('Y-m'),
                'start' => $nextMonth,
                'end' => $nextMonth->copy()->endOfMonth(),
                'due_at' => $nextMonth->copy()->addMonth()->endOfMonth()->setTime(23, 59, 0),
            ],
        ];
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function quarterlyPeriods(Carbon $now): array
    {
        $quarter = $now->quarter;
        $year = $now->year;
        $thisQStart = $now->copy()->startOfQuarter();
        $nextQStart = $thisQStart->copy()->addQuarter();
        $nextQ = $nextQStart->quarter;
        $nextQYear = $nextQStart->year;

        return [
            [
                'label' => "Q{$quarter}-{$year}",
                'start' => $thisQStart,
                'end' => $thisQStart->copy()->endOfQuarter(),
                'due_at' => $thisQStart->copy()->endOfQuarter()->addMonth()->endOfMonth()->setTime(23, 59, 0),
            ],
            [
                'label' => "Q{$nextQ}-{$nextQYear}",
                'start' => $nextQStart,
                'end' => $nextQStart->copy()->endOfQuarter(),
                'due_at' => $nextQStart->copy()->endOfQuarter()->addMonth()->endOfMonth()->setTime(23, 59, 0),
            ],
        ];
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function halfYearPeriods(Carbon $now): array
    {
        $year = $now->year;
        $half = $now->month <= 6 ? 'H1' : 'H2';
        $thisHStart = $half === 'H1'
            ? $now->copy()->startOfYear()
            : Carbon::create($year, 7, 1);

        $nextHStart = $thisHStart->copy()->addMonths(6);
        $nextHalf = $half === 'H1' ? 'H2' : 'H1';
        $nextHYear = $nextHalf === 'H1' ? $year + 1 : $year;

        return [
            [
                'label' => "{$half}-{$year}",
                'start' => $thisHStart,
                'end' => $thisHStart->copy()->addMonths(6)->subDay()->endOfDay(),
                'due_at' => $thisHStart->copy()->addMonths(7)->endOfMonth()->setTime(23, 59, 0),
            ],
            [
                'label' => "{$nextHalf}-{$nextHYear}",
                'start' => $nextHStart,
                'end' => $nextHStart->copy()->addMonths(6)->subDay()->endOfDay(),
                'due_at' => $nextHStart->copy()->addMonths(7)->endOfMonth()->setTime(23, 59, 0),
            ],
        ];
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function annualPeriods(Carbon $now): array
    {
        $year = $now->year;
        $thisStart = $now->copy()->startOfYear();
        $nextStart = $now->copy()->addYear()->startOfYear();

        return [
            [
                'label' => (string) $year,
                'start' => $thisStart,
                'end' => $thisStart->copy()->endOfYear(),
                'due_at' => Carbon::create($year + 1, 3, 31, 23, 59, 0), // Q1 following year
            ],
            [
                'label' => (string) ($year + 1),
                'start' => $nextStart,
                'end' => $nextStart->copy()->endOfYear(),
                'due_at' => Carbon::create($year + 2, 3, 31, 23, 59, 0),
            ],
        ];
    }
}
