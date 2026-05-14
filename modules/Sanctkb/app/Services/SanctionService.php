<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Services;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Modules\Sanctkb\Models\Sanction;

class SanctionService
{
    public function count(): int
    {
        return Sanction::count();
    }

    /**
     * @return Collection<int, array{id: int, due_date: string, obligation: string, instrument: string, status: string, days_until: int}>
     */
    public function forCalendar(DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        return Sanction::with('regulator')
            ->where('effective_date', '>=', $from->format('Y-m-d'))
            ->where('effective_date', '<=', $to->format('Y-m-d'))
            ->orderBy('effective_date')
            ->get()
            ->map(function (Sanction $s) {
                return [
                    'id'         => $s->id + 100000,
                    'due_date'   => $s->effective_date->format('Y-m-d'),
                    'obligation' => 'Sanction: '.$s->party_name,
                    'instrument' => $s->reference ?? '',
                    'status'     => 'info',
                    'days_until' => (int) now()->diffInDays($s->effective_date, false),
                ];
            });
    }
}
