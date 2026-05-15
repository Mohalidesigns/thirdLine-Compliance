<?php

declare(strict_types=1);

namespace Modules\Controls\Ccm\Rules;

use Modules\Controls\Contracts\CcmRule;
use Modules\Library\Models\Obligation;

class OverdueObligationsCcmRule implements CcmRule
{
    public function name(): string
    {
        return 'overdue_obligations';
    }

    public function cadence(): string
    {
        return 'daily';
    }

    public function threshold(): float
    {
        return 0.0;
    }

    /**
     * Evaluate overdue obligations.
     *
     * Severity is based on how many days past due the obligation is:
     *   < 30 days  → low
     *   30–90 days → medium
     *   > 90 days  → high
     *
     * Uses Eloquent so the BelongsToTenant global scope is applied automatically.
     */
    public function evaluate(): array
    {
        $today = now()->startOfDay();

        $obligations = Obligation::query()
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<', $today->toDateString())
            ->where('status', '!=', 'satisfied')
            ->get(['id', 'reference', 'title', 'next_due_date']);

        $breaches = [];

        foreach ($obligations as $obligation) {
            $overdueDays = (int) $today->diffInDays($obligation->next_due_date, false) * -1;
            $severity = $this->severityFromDays($overdueDays);

            $breaches[] = [
                'severity' => $severity,
                'subject' => [
                    'type' => Obligation::class,
                    'id' => $obligation->id,
                ],
                'detail' => "Obligation {$obligation->reference} is {$overdueDays} day(s) overdue.",
            ];
        }

        $count = count($breaches);

        return [
            'metric_value' => (float) $count,
            'breached' => $count > $this->threshold(),
            'breaches' => $breaches,
        ];
    }

    public function kciName(): string
    {
        return 'Overdue Regulatory Obligations';
    }

    private function severityFromDays(int $overdueDays): string
    {
        return match (true) {
            $overdueDays > 90 => 'high',
            $overdueDays >= 30 => 'medium',
            default => 'low',
        };
    }
}
