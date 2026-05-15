<?php

declare(strict_types=1);

namespace Modules\Returns\Services;

use App\Services\AuditWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Returns\Models\ReturnApproval;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnReminder;
use Modules\Returns\Models\ReturnRun;

class ReturnsService
{
    public function __construct(
        private readonly AuditWriter $auditWriter,
        private readonly StrategyRegistry $strategyRegistry,
    ) {}

    /**
     * Create a ReturnRun for a given definition and period.
     * Idempotent on (return_definition_id, period_label).
     * Schedules 30/14/7/1-day reminders on first creation.
     */
    public function createReturnRun(
        ReturnDefinition $def,
        string $periodLabel,
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $dueAt,
    ): ReturnRun {
        return DB::transaction(function () use ($def, $periodLabel, $periodStart, $periodEnd, $dueAt): ReturnRun {
            $existing = ReturnRun::withoutGlobalScopes()
                ->where('return_definition_id', $def->id)
                ->where('period_label', $periodLabel)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $run = ReturnRun::create([
                'tenant_id' => $def->tenant_id,
                'return_definition_id' => $def->id,
                'period_label' => $periodLabel,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'due_at' => $dueAt,
                'status' => 'scheduled',
            ]);

            $this->scheduleReminders($run, $dueAt);

            return $run;
        });
    }

    /**
     * Generate the payload file for a run via its strategy.
     * Updates payload_path on the run.
     * No-op for manual channel (returns null).
     */
    public function generatePayload(ReturnRun $run): ?string
    {
        $strategy = $this->strategyRegistry->resolve($run->definition);
        $path = $strategy->buildPayload($run);

        if ($path !== null) {
            $run->update(['payload_path' => $path]);
        }

        return $path;
    }

    /**
     * Maker submits the run for review.
     * Status → in_progress. Records maker_submit approval row.
     *
     * @throws \RuntimeException if run is not in 'scheduled' or 'in_progress'
     */
    public function submitForReview(ReturnRun $run, int $actorId, ?string $notes): ReturnApproval
    {
        return DB::transaction(function () use ($run, $actorId, $notes): ReturnApproval {
            if (! in_array($run->status, ['scheduled', 'in_progress'], true)) {
                throw new \RuntimeException("Cannot submit for review from status '{$run->status}'.");
            }

            $run->update([
                'status' => 'in_progress',
                'maker_id' => $actorId,
            ]);

            $approval = ReturnApproval::create([
                'tenant_id' => $run->tenant_id,
                'return_run_id' => $run->id,
                'step' => 'maker_submit',
                'actor_id' => $actorId,
                'decision' => 'submitted',
                'notes' => $notes,
                'acted_at' => now(),
                'created_at' => now(),
            ]);

            $this->auditWriter->record(
                action: 'return_run.maker_submitted',
                subject: $run,
                context: ['actor_id' => $actorId, 'period_label' => $run->period_label],
            );

            return $approval;
        });
    }

    /**
     * Checker approves the run at checker_review step.
     * Must be a different user from the maker.
     *
     * @throws \RuntimeException if run is not in_progress, or actor is maker
     */
    public function approve(ReturnRun $run, int $actorId, ?string $notes): ReturnApproval
    {
        return DB::transaction(function () use ($run, $actorId, $notes): ReturnApproval {
            if ($run->status !== 'in_progress') {
                throw new \RuntimeException("Cannot approve from status '{$run->status}'.");
            }

            if ($run->maker_id === $actorId) {
                throw new \RuntimeException('Checker must be a different user from the maker.');
            }

            $run->update(['checker_id' => $actorId]);

            $approval = ReturnApproval::create([
                'tenant_id' => $run->tenant_id,
                'return_run_id' => $run->id,
                'step' => 'checker_review',
                'actor_id' => $actorId,
                'decision' => 'approved',
                'notes' => $notes,
                'acted_at' => now(),
                'created_at' => now(),
            ]);

            $this->auditWriter->record(
                action: 'return_run.checker_approved',
                subject: $run,
                context: ['actor_id' => $actorId],
            );

            return $approval;
        });
    }

    /**
     * Approver signs off and triggers submission.
     * Must differ from both maker and checker.
     * Calls the strategy to submit (unless manual, where manualReference is used).
     * Status → submitted_pending_ack.
     *
     * @throws \RuntimeException on constraint violations
     */
    public function signOffAndSubmit(
        ReturnRun $run,
        int $actorId,
        ?string $notes,
        ?string $manualReference,
    ): ReturnRun {
        return DB::transaction(function () use ($run, $actorId, $notes, $manualReference): ReturnRun {
            if ($run->status !== 'in_progress') {
                throw new \RuntimeException("Cannot sign off from status '{$run->status}'.");
            }

            if ($run->maker_id === $actorId) {
                throw new \RuntimeException('Approver must be a different user from the maker.');
            }

            if ($run->checker_id === $actorId) {
                throw new \RuntimeException('Approver must be a different user from the checker.');
            }

            // Build payload if not yet generated
            if ($run->payload_path === null) {
                $this->generatePayload($run->fresh());
                $run->refresh();
            }

            // Submit via strategy
            $strategy = $this->strategyRegistry->resolve($run->definition);
            $result = $strategy->submit($run);

            $reference = $manualReference ?? $result['reference'];
            $submittedAt = $result['submitted_at'];

            $run->update([
                'status' => 'submitted_pending_ack',
                'approver_id' => $actorId,
                'submission_reference' => $reference,
                'submitted_at' => $submittedAt,
            ]);

            ReturnApproval::create([
                'tenant_id' => $run->tenant_id,
                'return_run_id' => $run->id,
                'step' => 'approver_sign_off',
                'actor_id' => $actorId,
                'decision' => 'approved',
                'notes' => $notes,
                'acted_at' => now(),
                'created_at' => now(),
            ]);

            $this->auditWriter->record(
                action: 'return_run.approver_signed_off',
                subject: $run,
                context: [
                    'actor_id' => $actorId,
                    'reference' => $reference,
                    'submitted_at' => $submittedAt->toIso8601String(),
                ],
            );

            return $run->fresh();
        });
    }

    /**
     * Record acknowledgement from the regulator.
     * Sets acknowledged_at, acknowledgement_path, status → acknowledged.
     */
    public function recordAcknowledgement(
        ReturnRun $run,
        string $acknowledgementPath,
        int $actorId,
    ): ReturnRun {
        return DB::transaction(function () use ($run, $acknowledgementPath, $actorId): ReturnRun {
            $run->update([
                'status' => 'acknowledged',
                'acknowledged_at' => now(),
                'acknowledgement_path' => $acknowledgementPath,
            ]);

            $this->auditWriter->record(
                action: 'return_run.acknowledged',
                subject: $run,
                context: [
                    'actor_id' => $actorId,
                    'acknowledgement_path' => $acknowledgementPath,
                ],
            );

            return $run->fresh();
        });
    }

    /**
     * Mark a run as late. Called by the scheduler when overdue.
     * Only flips runs that are still actionable (scheduled or in_progress).
     */
    public function markLate(ReturnRun $run): ReturnRun
    {
        return DB::transaction(function () use ($run): ReturnRun {
            $run->update(['status' => 'late']);

            $this->auditWriter->record(
                action: 'return_run.marked_late',
                subject: $run,
                context: ['due_at' => $run->due_at->toIso8601String()],
            );

            return $run->fresh();
        });
    }

    /**
     * Dashboard statistics for the returns module.
     *
     * @return array<string, mixed>
     */
    public function dashboardStats(int $tenantId): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $totalActiveDefinitions = ReturnDefinition::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->count();

        $runsDueThisMonth = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereBetween('due_at', [$monthStart, $monthEnd])
            ->count();

        $runsSubmittedThisMonth = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['submitted_pending_ack', 'acknowledged'])
            ->whereBetween('submitted_at', [$monthStart, $monthEnd])
            ->count();

        $runsAcknowledgedThisMonth = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'acknowledged')
            ->whereBetween('acknowledged_at', [$monthStart, $monthEnd])
            ->count();

        $runsLate = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'late')
            ->count();

        // On-time rate in the last 30 days: acknowledged / (acknowledged + late)
        $thirtyDaysAgo = now()->subDays(30);
        $recentAcknowledged = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'acknowledged')
            ->where('due_at', '>=', $thirtyDaysAgo)
            ->count();
        $recentLate = ReturnRun::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'late')
            ->where('due_at', '>=', $thirtyDaysAgo)
            ->count();
        $total30d = $recentAcknowledged + $recentLate;
        $onTimeRate = $total30d > 0 ? round($recentAcknowledged / $total30d, 4) : 1.0;

        // Per-regulator breakdown
        $regulators = ReturnDefinition::regulatorLabels();
        $byRegulator = [];
        foreach (array_keys($regulators) as $code) {
            $defIds = ReturnDefinition::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('regulator', $code)
                ->whereNull('deleted_at')
                ->pluck('id');

            if ($defIds->isEmpty()) {
                continue;
            }

            $due = ReturnRun::withoutGlobalScopes()
                ->whereIn('return_definition_id', $defIds)
                ->whereBetween('due_at', [$monthStart, $monthEnd])
                ->count();

            $submitted = ReturnRun::withoutGlobalScopes()
                ->whereIn('return_definition_id', $defIds)
                ->whereIn('status', ['submitted_pending_ack', 'acknowledged'])
                ->count();

            $late = ReturnRun::withoutGlobalScopes()
                ->whereIn('return_definition_id', $defIds)
                ->where('status', 'late')
                ->count();

            $ackCount = ReturnRun::withoutGlobalScopes()
                ->whereIn('return_definition_id', $defIds)
                ->where('status', 'acknowledged')
                ->where('due_at', '>=', $thirtyDaysAgo)
                ->count();

            $lateCount = ReturnRun::withoutGlobalScopes()
                ->whereIn('return_definition_id', $defIds)
                ->where('status', 'late')
                ->where('due_at', '>=', $thirtyDaysAgo)
                ->count();

            $tot = $ackCount + $lateCount;
            $byRegulator[] = [
                'regulator' => $code,
                'label' => $regulators[$code],
                'due' => $due,
                'submitted' => $submitted,
                'late' => $late,
                'on_time_rate' => $tot > 0 ? round($ackCount / $tot, 4) : 1.0,
            ];
        }

        return [
            'total_active_definitions' => $totalActiveDefinitions,
            'runs_due_this_month' => $runsDueThisMonth,
            'runs_submitted_this_month' => $runsSubmittedThisMonth,
            'runs_acknowledged_this_month' => $runsAcknowledgedThisMonth,
            'runs_late' => $runsLate,
            'on_time_rate_30d' => $onTimeRate,
            'by_regulator' => $byRegulator,
        ];
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    /**
     * Schedule reminder rows at 30/14/7/1 days before due_at.
     * Each day-bucket that is in the future gets a reminder row.
     */
    private function scheduleReminders(ReturnRun $run, Carbon $dueAt): void
    {
        foreach ([30, 14, 7, 1] as $daysBefore) {
            $reminderAt = $dueAt->copy()->subDays($daysBefore);

            if ($reminderAt->isFuture()) {
                ReturnReminder::create([
                    'tenant_id' => $run->tenant_id,
                    'return_run_id' => $run->id,
                    'reminder_at' => $reminderAt,
                    'days_before_due' => $daysBefore,
                    'channel' => 'in_app',
                    'created_at' => now(),
                ]);
            }
        }
    }
}
