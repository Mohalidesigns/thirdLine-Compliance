<?php

declare(strict_types=1);

namespace Modules\Library\Services;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\Obligation;

class InstrumentService
{
    public function findInstrument(int $id): Instrument
    {
        return Instrument::with([
            'regulator',
            'instrumentType',
            'nature',
            'status',
            'areaOfFocus',
            'riskRating',
        ])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedList(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Instrument::with(['regulator', 'instrumentType', 'riskRating', 'status'])
            ->orderBy('source_title');

        if (! empty($filters['search'])) {
            $likeOp = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('source_title', $likeOp, '%'.$filters['search'].'%');
        }

        if (! empty($filters['regulator'])) {
            $query->whereHas('regulator', fn ($q) => $q->where('code', $filters['regulator']));
        }

        if (! empty($filters['item_type'])) {
            $query->whereHas('instrumentType', fn ($q) => $q->where('name', $filters['item_type']));
        }

        if (! empty($filters['risk_rating'])) {
            $likeOp = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->whereHas('riskRating', fn ($q) => $q->where('name', $likeOp, $filters['risk_rating']));
        }

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, Instrument>
     */
    public function searchUniverse(string $query): Collection
    {
        $likeOp = \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return Instrument::with(['regulator', 'instrumentType', 'riskRating'])
            ->where(function ($q) use ($query, $likeOp): void {
                $q->where('source_title', $likeOp, '%'.$query.'%')
                    ->orWhere('objectives', $likeOp, '%'.$query.'%');
            })
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, Instrument>
     */
    public function forCalendar(DateTimeInterface $from, DateTimeInterface $to): Collection
    {
        return Instrument::with(['regulator', 'status'])
            ->whereNotNull('date_commence')
            ->whereBetween('date_commence', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->orderBy('date_commence')
            ->get();
    }

    /**
     * @return array<int, array{id: int, name: string, count: int}>
     */
    public function countByRegulator(): array
    {
        return DB::table('instruments')
            ->join('regulators', 'regulators.id', '=', 'instruments.regulator_id')
            ->where('instruments.tenant_id', Instrument::currentTenantId())
            ->whereNull('instruments.deleted_at')
            ->select('regulators.id', 'regulators.name', DB::raw('COUNT(*) as count'))
            ->groupBy('regulators.id', 'regulators.name')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'count' => (int) $row->count,
            ])
            ->toArray();
    }

    public function count(): int
    {
        return Instrument::count();
    }

    public function obligationCount(): int
    {
        return Obligation::count();
    }

    public function deadlineCount(): int
    {
        return Obligation::whereNotNull('next_due_date')
            ->where('next_due_date', '<=', now()->addDays(30)->toDateString())
            ->whereNotIn('status', ['satisfied'])
            ->count();
    }

    /**
     * @return array<int, array{id: int, obligation: string, due_date: string, status: string}>
     */
    public function upcomingObligations(int $limit = 7): array
    {
        return Obligation::with(['instrument.regulator'])
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '>=', now()->toDateString())
            ->whereNotIn('status', ['satisfied'])
            ->orderBy('next_due_date')
            ->limit($limit)
            ->get()
            ->map(fn (Obligation $o) => [
                'id'         => $o->id,
                'obligation' => $o->title,
                'due_date'   => $o->next_due_date?->format('Y-m-d') ?? '',
                'status'     => $o->status === 'open' ? 'Pending' : ucfirst(str_replace('_', ' ', $o->status)),
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, nature: string, count: int}>
     */
    public function countByNature(): array
    {
        return DB::table('instruments')
            ->join('natures', 'natures.id', '=', 'instruments.nature_id')
            ->where('instruments.tenant_id', Instrument::currentTenantId())
            ->whereNull('instruments.deleted_at')
            ->select('natures.name as nature', DB::raw('COUNT(*) as count'))
            ->groupBy('natures.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'id'     => crc32($row->nature),
                'nature' => $row->nature,
                'count'  => (int) $row->count,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{label: string, value: int, color: string}>
     */
    public function countByRisk(): array
    {
        $riskColors = [
            'High'   => '#DD6B20',
            'Medium' => '#D4AF37',
            'Low'    => '#2D7D46',
        ];

        return DB::table('instruments')
            ->join('risk_ratings', 'risk_ratings.id', '=', 'instruments.risk_rating_id')
            ->where('instruments.tenant_id', Instrument::currentTenantId())
            ->whereNull('instruments.deleted_at')
            ->select('risk_ratings.name as label', DB::raw('COUNT(*) as count'))
            ->groupBy('risk_ratings.name')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->count,
                'color' => $riskColors[$row->label] ?? '#718096',
            ])
            ->toArray();
    }

    /**
     * @return Collection<int, Obligation>
     */
    public function findOverdue(): Collection
    {
        return Obligation::with(['instrument.regulator'])
            ->where('status', 'overdue')
            ->orWhere(function ($q): void {
                $q->whereNotNull('next_due_date')
                    ->where('next_due_date', '<', now()->toDateString())
                    ->where('status', '!=', 'satisfied');
            })
            ->orderBy('next_due_date')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, due_date: string, obligation: string, instrument: string, status: string, days_until: int}>
     */
    public function obligationsForCalendar(DateTimeInterface $from, DateTimeInterface $to): \Illuminate\Support\Collection
    {
        $obligations = Obligation::with(['instrument.regulator'])
            ->whereNotNull('next_due_date')
            ->whereBetween('next_due_date', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->whereNotIn('status', ['satisfied'])
            ->orderBy('next_due_date')
            ->get();

        $today = now()->toDateString();

        return $obligations->map(function (Obligation $obl) use ($today) {
            $dueDate = $obl->next_due_date->format('Y-m-d');
            $daysUntil = (int) now()->diffInDays($obl->next_due_date, false);

            $status = match (true) {
                $dueDate < $today => 'overdue',
                $daysUntil <= 7 => 'medium',
                $daysUntil <= 30 => 'info',
                default => 'low',
            };

            return [
                'id' => $obl->id,
                'due_date' => $dueDate,
                'obligation' => $obl->title,
                'instrument' => $obl->instrument?->source_title ?? '',
                'status' => $obl->status === 'overdue' ? 'overdue' : $status,
                'days_until' => $daysUntil,
            ];
        });
    }
}
