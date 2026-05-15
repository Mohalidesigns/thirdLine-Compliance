<?php

declare(strict_types=1);

namespace Modules\Rcsa\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAssessmentCycle;

class RcsaService
{
    public function __construct(
        private readonly RiskScoringService $scoring,
    ) {}

    public function paginatedCycles(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = RiskAssessmentCycle::query()
            ->with('leadAssessor')
            ->withCount(['risks', 'risks as high_risk_count' => fn ($q) => $q->whereIn('residual_rating', ['high', 'critical'])])
            ->orderByDesc('updated_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($term, $likeOp): void {
                $q->where('name', $likeOp, "%{$term}%")
                    ->orWhere('reference', $likeOp, "%{$term}%");
            });
        }

        if (! empty($filters['lob'])) {
            $query->where('lob', $filters['lob']);
        }

        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (! empty($filters['year'])) {
            $query->where('cycle_year', $filters['year']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginatedRisks(int $cycleId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Risk::query()
            ->where('cycle_id', $cycleId)
            ->orderBy('reference');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['rating'])) {
            $query->where('residual_rating', $filters['rating']);
        }

        if (! empty($filters['appetite_breached'])) {
            $riskIds = Risk::withoutGlobalScopes()
                ->where('cycle_id', $cycleId)
                ->get()
                ->filter(fn ($r) => $this->scoring->breachesAppetite($r->id))
                ->pluck('id');
            $query->whereIn('id', $riskIds);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): RiskAssessmentCycle
    {
        return RiskAssessmentCycle::findOrFail($id);
    }

    public function findRisk(int $id): Risk
    {
        return Risk::findOrFail($id);
    }

    public function createCycle(array $data): RiskAssessmentCycle
    {
        return DB::transaction(function () use ($data): RiskAssessmentCycle {
            $cycle = RiskAssessmentCycle::create($data);

            if (! empty($data['started_at'])) {
                $cycle->sla_due_date = \Carbon\Carbon::parse($data['started_at'])->addDays(30)->toDateString();
                $cycle->save();
            }

            return $cycle;
        });
    }

    public function updateCycle(RiskAssessmentCycle $cycle, array $data): RiskAssessmentCycle
    {
        $cycle->update($data);

        return $cycle;
    }

    public function createRisk(array $data): Risk
    {
        return Risk::create($data);
    }

    public function updateRisk(Risk $risk, array $data): Risk
    {
        $risk->update($data);

        return $risk;
    }

    public function transitionCycle(RiskAssessmentCycle $cycle, string $to, ?int $actorId = null): RiskAssessmentCycle
    {
        $stateMap = [
            'planning' => \Modules\Rcsa\States\RiskAssessmentCycle\Planning::class,
            'data_capture' => \Modules\Rcsa\States\RiskAssessmentCycle\DataCapture::class,
            'scoring' => \Modules\Rcsa\States\RiskAssessmentCycle\Scoring::class,
            'in_review' => \Modules\Rcsa\States\RiskAssessmentCycle\InReview::class,
            'signed_off' => \Modules\Rcsa\States\RiskAssessmentCycle\SignedOff::class,
            'closed' => \Modules\Rcsa\States\RiskAssessmentCycle\Closed::class,
        ];

        if (! isset($stateMap[$to])) {
            throw new \InvalidArgumentException("Unknown target state: {$to}");
        }

        DB::transaction(function () use ($cycle, $stateMap, $to): void {
            $cycle->state->transitionTo($stateMap[$to]);

            if ($to === 'data_capture' && $cycle->started_at === null) {
                $cycle->started_at = now()->toDateString();
                $cycle->sla_due_date = now()->addDays(30)->toDateString();
            }

            if ($to === 'closed') {
                $cycle->closed_at = now()->toDateString();
            }

            $cycle->save();
        });

        return $cycle->fresh();
    }

    public function cycleSummary(int $cycleId): array
    {
        $risks = Risk::where('cycle_id', $cycleId)->get();

        $byRating = ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0];
        foreach ($risks as $risk) {
            if ($risk->residual_rating !== null && isset($byRating[$risk->residual_rating])) {
                $byRating[$risk->residual_rating]++;
            }
        }

        $appetiteBreaches = $risks->filter(fn ($r) => $this->scoring->breachesAppetite($r->id))->count();

        $cycle = RiskAssessmentCycle::withoutGlobalScopes()->find($cycleId);
        $daysToSla = $cycle?->slaDaysRemaining();
        $workshopsCount = \Modules\Rcsa\Models\RiskWorkshopNote::where('cycle_id', $cycleId)->count();

        return [
            'risks_total' => $risks->count(),
            'by_rating' => $byRating,
            'appetite_breaches' => $appetiteBreaches,
            'days_to_sla' => $daysToSla,
            'workshops_count' => $workshopsCount,
        ];
    }

    public function heatmap(int $cycleId): array
    {
        $cycle = RiskAssessmentCycle::withoutGlobalScopes()->findOrFail($cycleId);
        $risks = Risk::where('cycle_id', $cycleId)->get();

        $maxDim = $cycle->methodology === '5x5' ? 5 : 3;
        $matrix = [];

        for ($l = 1; $l <= $maxDim; $l++) {
            for ($i = 1; $i <= $maxDim; $i++) {
                $matrix[$l][$i] = [];
            }
        }

        foreach ($risks as $risk) {
            $l = $risk->residual_likelihood ?? $risk->inherent_likelihood;
            $i = $risk->residual_impact ?? $risk->inherent_impact;

            if ($l !== null && $i !== null && $l >= 1 && $l <= $maxDim && $i >= 1 && $i <= $maxDim) {
                $matrix[$l][$i][] = $risk->id;
            }
        }

        return [
            'matrix' => $matrix,
            'methodology' => $cycle->methodology,
        ];
    }
}
