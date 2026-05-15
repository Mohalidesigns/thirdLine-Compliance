<?php

declare(strict_types=1);

namespace Modules\Returns\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Returns\Models\ReturnApproval;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnRun;

class ReturnRunSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        // Only seed runs for monthly/quarterly/annual definitions to keep seed realistic
        $definitions = ReturnDefinition::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('frequency', ['monthly', 'quarterly', 'annual'])
            ->get();

        $now = now();

        foreach ($definitions as $def) {
            $this->seedRunsForDefinition($def, $now);
        }
    }

    private function seedRunsForDefinition(ReturnDefinition $def, Carbon $now): void
    {
        $frequency = $def->frequency;

        // Build 3 historical periods + 1 upcoming
        $periods = match ($frequency) {
            'monthly' => $this->monthlyPeriods($now),
            'quarterly' => $this->quarterlyPeriods($now),
            'annual' => $this->annualPeriods($now),
            default => [],
        };

        $statusCycle = ['acknowledged', 'acknowledged', 'submitted_pending_ack', 'scheduled'];
        $statusIndex = 0;

        foreach ($periods as $period) {
            $status = $statusCycle[$statusIndex % count($statusCycle)];
            $statusIndex++;

            $existingRun = ReturnRun::withoutGlobalScopes()
                ->where('return_definition_id', $def->id)
                ->where('period_label', $period['label'])
                ->first();

            if ($existingRun !== null) {
                continue;
            }

            $runData = [
                'tenant_id' => $def->tenant_id,
                'return_definition_id' => $def->id,
                'period_label' => $period['label'],
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
                'due_at' => $period['due_at'],
                'status' => $status,
            ];

            if ($status === 'acknowledged') {
                $runData['maker_id'] = 1;
                $runData['checker_id'] = 2;
                $runData['approver_id'] = 3;
                $runData['submission_reference'] = strtoupper(substr(md5(uniqid('', true)), 0, 8));
                $runData['submitted_at'] = $period['due_at']->copy()->subDays(2);
                $runData['acknowledged_at'] = $period['due_at']->copy()->subDay();
                $runData['acknowledgement_path'] = "returns/seed/ack_{$def->code}_{$period['label']}.pdf";
            } elseif ($status === 'submitted_pending_ack') {
                $runData['maker_id'] = 1;
                $runData['checker_id'] = 2;
                $runData['approver_id'] = 3;
                $runData['submission_reference'] = strtoupper(substr(md5(uniqid('', true)), 0, 8));
                $runData['submitted_at'] = now()->subDays(3);
            } elseif ($status === 'late') {
                $runData['status'] = 'late';
            }

            $run = ReturnRun::create($runData);

            // Seed approval records for non-scheduled runs
            if (in_array($status, ['acknowledged', 'submitted_pending_ack'], true)) {
                ReturnApproval::create([
                    'tenant_id' => $def->tenant_id,
                    'return_run_id' => $run->id,
                    'step' => 'maker_submit',
                    'actor_id' => 1,
                    'decision' => 'submitted',
                    'notes' => 'Prepared and submitted for review.',
                    'acted_at' => $run->submitted_at?->subDays(3) ?? now()->subDays(5),
                    'created_at' => now()->subDays(5),
                ]);

                ReturnApproval::create([
                    'tenant_id' => $def->tenant_id,
                    'return_run_id' => $run->id,
                    'step' => 'checker_review',
                    'actor_id' => 2,
                    'decision' => 'approved',
                    'notes' => 'Reviewed and approved.',
                    'acted_at' => $run->submitted_at?->subDays(2) ?? now()->subDays(4),
                    'created_at' => now()->subDays(4),
                ]);

                ReturnApproval::create([
                    'tenant_id' => $def->tenant_id,
                    'return_run_id' => $run->id,
                    'step' => 'approver_sign_off',
                    'actor_id' => 3,
                    'decision' => 'approved',
                    'notes' => 'Signed off and submitted to regulator.',
                    'acted_at' => $run->submitted_at ?? now()->subDays(3),
                    'created_at' => now()->subDays(3),
                ]);
            }
        }
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function monthlyPeriods(Carbon $now): array
    {
        $periods = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = $now->copy()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $dueAt = $start->copy()->addMonth()->endOfMonth()->setTime(23, 59, 0);
            $periods[] = [
                'label' => $start->format('Y-m'),
                'start' => $start,
                'end' => $end,
                'due_at' => $dueAt,
            ];
        }

        return $periods;
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function quarterlyPeriods(Carbon $now): array
    {
        $periods = [];
        for ($i = 3; $i >= 0; $i--) {
            $ref = $now->copy()->subMonths($i * 3);
            $start = $ref->copy()->startOfQuarter();
            $end = $ref->copy()->endOfQuarter();
            $dueAt = $end->copy()->addMonth()->endOfMonth()->setTime(23, 59, 0);
            $label = "Q{$ref->quarter}-{$ref->year}";
            // deduplicate
            if (! collect($periods)->contains('label', $label)) {
                $periods[] = [
                    'label' => $label,
                    'start' => $start,
                    'end' => $end,
                    'due_at' => $dueAt,
                ];
            }
        }

        return $periods;
    }

    /** @return array<int, array{label: string, start: Carbon, end: Carbon, due_at: Carbon}> */
    private function annualPeriods(Carbon $now): array
    {
        $periods = [];
        for ($i = 3; $i >= 0; $i--) {
            $ref = $now->copy()->subYears($i);
            $start = $ref->copy()->startOfYear();
            $end = $ref->copy()->endOfYear();
            $dueAt = Carbon::create($ref->year + 1, 3, 31, 23, 59, 0);
            $label = (string) $ref->year;
            if (! collect($periods)->contains('label', $label)) {
                $periods[] = [
                    'label' => $label,
                    'start' => $start,
                    'end' => $end,
                    'due_at' => $dueAt,
                ];
            }
        }

        return $periods;
    }
}
