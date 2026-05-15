<?php

declare(strict_types=1);

namespace Modules\Controls\Ccm\Rules;

use Illuminate\Support\Facades\DB;
use Modules\Controls\Contracts\CcmRule;

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

    public function evaluate(): array
    {
        $cutoff = now()->subDays(60)->toDateString();

        $count = DB::table('policies')
            ->where('state', 'in_force')
            ->whereNotNull('next_review_date')
            ->where('next_review_date', '<', $cutoff)
            ->whereNull('deleted_at')
            ->count();

        return [
            'metric_value' => (float) $count,
            'breached' => $count > $this->threshold(),
        ];
    }

    public function kciName(): string
    {
        return 'Stale In-Force Policies (review overdue > 60 days)';
    }
}
