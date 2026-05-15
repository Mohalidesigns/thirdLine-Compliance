<?php

declare(strict_types=1);

namespace Modules\Controls\Ccm\Rules;

use Modules\Controls\Contracts\CcmRule;
use Modules\Policy\Models\Policy;

class StalePoliciesCcmRule implements CcmRule
{
    public function name(): string
    {
        return 'stale_policies';
    }

    public function cadence(): string
    {
        return 'weekly';
    }

    public function threshold(): float
    {
        return 0.0;
    }

    /**
     * Evaluate stale in-force policies.
     *
     * A policy is stale when it is in_force and its next_review_date is in the past.
     * Severity is based on how many days past the review date:
     *   < 30 days  → low
     *   30–90 days → medium
     *   > 90 days  → high
     *
     * Uses Eloquent so the BelongsToTenant global scope is applied automatically.
     */
    public function evaluate(): array
    {
        $today = now()->startOfDay();

        $policies = Policy::query()
            ->where('state', 'in_force')
            ->whereNotNull('next_review_date')
            ->where('next_review_date', '<', $today->toDateString())
            ->get(['id', 'reference', 'title', 'next_review_date']);

        $breaches = [];

        foreach ($policies as $policy) {
            $staleDays = (int) $today->diffInDays($policy->next_review_date, false) * -1;
            $severity = $this->severityFromDays($staleDays);

            $breaches[] = [
                'severity' => $severity,
                'subject' => [
                    'type' => Policy::class,
                    'id' => $policy->id,
                ],
                'detail' => "Policy {$policy->reference} review is {$staleDays} day(s) overdue.",
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
        return 'Stale In-Force Policies (review overdue)';
    }

    private function severityFromDays(int $staleDays): string
    {
        return match (true) {
            $staleDays > 90 => 'high',
            $staleDays >= 30 => 'medium',
            default => 'low',
        };
    }
}
