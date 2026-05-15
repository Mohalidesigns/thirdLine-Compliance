<?php

declare(strict_types=1);

namespace Modules\Controls\Ccm\Rules;

use Illuminate\Support\Facades\DB;
use Modules\Controls\Contracts\CcmRule;

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

    public function evaluate(): array
    {
        $count = DB::table('obligations')
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<', now()->toDateString())
            ->where('status', '!=', 'satisfied')
            ->whereNull('deleted_at')
            ->count();

        return [
            'metric_value' => (float) $count,
            'breached' => $count > $this->threshold(),
        ];
    }

    public function kciName(): string
    {
        return 'Overdue Regulatory Obligations';
    }
}
