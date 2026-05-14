<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Library\Models\Regulator;
use Modules\Sanctkb\Http\Requests\StoreSanctionRequest;
use Modules\Sanctkb\Http\Requests\UpdateSanctionRequest;
use Modules\Sanctkb\Models\Sanction;

class SanctionsController extends Controller
{

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'severity']);

        $query = Sanction::with('regulator')->orderByDesc('effective_date');

        if (! empty($filters['search'])) {
            $query->fullText($filters['search']);
        }

        if (! empty($filters['category'])) {
            $query->whereHas('regulator', fn ($q) => $q->where('code', $filters['category']));
        }

        $paginated = $query->paginate(25);

        $exposureSummary = null;
        if (! empty($filters['search'])) {
            $exposureSummary = [
                'count' => $paginated->total(),
                'highestSeverity' => 'high',
            ];
        }

        $sanctions = $paginated->through(function (Sanction $s) {
            return [
                'id' => $s->id,
                'entry_no' => str_pad((string) $s->id, 4, '0', STR_PAD_LEFT),
                'category' => $s->regulator->code ?? 'N/A',
                'provision' => $s->reference ? $s->reference.' §'.$s->section : ($s->reference ?? $s->section ?? ''),
                'description' => $s->offence,
                'jurisdiction' => 'Nigeria',
                'severity' => $this->mapSeverity($s->penalty_type, $s->amount_naira),
            ];
        });

        $categories = Regulator::orderBy('code')
            ->get()
            ->map(fn ($r) => ['value' => $r->code, 'label' => $r->code])
            ->toArray();

        return Inertia::render('Sanctions/Index', [
            'sanctions' => $sanctions,
            'filters' => $filters,
            'categories' => $categories,
            'severities' => [
                ['value' => 'critical', 'label' => 'Critical'],
                ['value' => 'high', 'label' => 'High'],
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'low', 'label' => 'Low'],
            ],
            'exposureSummary' => $exposureSummary,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Sanctions/Create', [
            'regulators' => Regulator::orderBy('name')->get(['id', 'code', 'name'])
                ->map(fn ($r) => ['value' => $r->id, 'label' => $r->code.' — '.$r->name])
                ->values()
                ->toArray(),
        ]);
    }

    public function store(StoreSanctionRequest $request): RedirectResponse
    {
        Sanction::create($request->validated());

        $this->refreshPenaltyExposure();

        return redirect()->route('sanctions.index')
            ->with('flash', ['type' => 'success', 'message' => 'Sanction entry created.']);
    }

    public function show(int $id): Response
    {
        $sanction = Sanction::with('regulator')->findOrFail($id);

        return Inertia::render('Sanctions/Show', ['sanction' => $sanction]);
    }

    public function edit(int $id): Response
    {
        $sanction = Sanction::findOrFail($id);

        return Inertia::render('Sanctions/Edit', [
            'sanction' => $sanction,
            'regulators' => Regulator::orderBy('name')->get(['id', 'code', 'name'])
                ->map(fn ($r) => ['value' => $r->id, 'label' => $r->code.' — '.$r->name])
                ->values()
                ->toArray(),
        ]);
    }

    public function update(UpdateSanctionRequest $request, int $id): RedirectResponse
    {
        $sanction = Sanction::findOrFail($id);
        $sanction->update($request->validated());

        $this->refreshPenaltyExposure();

        return redirect()->route('sanctions.index')
            ->with('flash', ['type' => 'success', 'message' => 'Sanction entry updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $sanction = Sanction::findOrFail($id);
        $sanction->delete();
        $this->refreshPenaltyExposure();

        return redirect()->route('sanctions.index')
            ->with('flash', ['type' => 'success', 'message' => 'Sanction entry deleted.']);
    }

    private function refreshPenaltyExposure(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY vw_penalty_exposure');
        }
    }

    private function mapSeverity(string $penaltyType, mixed $amount): string
    {
        if ($penaltyType === 'license_action') {
            return 'critical';
        }
        if ($penaltyType === 'monetary' && $amount !== null) {
            $amt = (float) $amount;
            if ($amt >= 50_000_000) {
                return 'critical';
            }
            if ($amt >= 10_000_000) {
                return 'high';
            }
            if ($amt >= 1_000_000) {
                return 'medium';
            }
        }

        return 'low';
    }
}
