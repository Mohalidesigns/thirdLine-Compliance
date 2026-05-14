<?php

declare(strict_types=1);

namespace Modules\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Library\Http\Requests\StoreObligationRequest;
use Modules\Library\Http\Requests\UpdateObligationRequest;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\Obligation;

class ObligationsController extends Controller
{

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'instrument', 'nature', 'status', 'due_before']);

        $query = Obligation::with(['instrument.regulator', 'instrument.nature'])
            ->orderBy('next_due_date');

        if (! empty($filters['search'])) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($filters, $likeOp): void {
                $q->where('title', $likeOp, '%'.$filters['search'].'%')
                    ->orWhere('description', $likeOp, '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['instrument'])) {
            $query->where('instrument_id', $filters['instrument']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['due_before'])) {
            $query->whereDate('next_due_date', '<=', $filters['due_before']);
        }

        $paginated = $query->paginate(25);

        $obligations = $paginated->through(function (Obligation $o) {
            return [
                'id' => $o->id,
                'ref_code' => 'OBL-'.str_pad((string) $o->id, 4, '0', STR_PAD_LEFT),
                'description' => $o->description,
                'instrument' => $o->instrument?->source_title ?? '',
                'nature' => $o->instrument?->nature?->name ?? '',
                'due_date' => $o->next_due_date?->format('Y-m-d') ?? '',
                'status' => $this->mapStatus($o->status, $o->next_due_date?->format('Y-m-d')),
            ];
        });

        $instrumentOptions = Instrument::with('regulator')
            ->orderBy('source_title')
            ->get(['id', 'source_title', 'regulator_id'])
            ->map(fn ($i) => ['value' => (string) $i->id, 'label' => $i->source_title])
            ->toArray();

        return Inertia::render('Obligations/Index', [
            'obligations' => $obligations,
            'filters' => $filters,
            'instruments' => $instrumentOptions,
            'natures' => [
                ['value' => 'Statutory', 'label' => 'Statutory'],
                ['value' => 'Regulatory', 'label' => 'Regulatory'],
                ['value' => 'Industry Standard', 'label' => 'Industry Standard'],
                ['value' => 'Internal', 'label' => 'Internal'],
            ],
            'statuses' => [
                ['value' => 'open', 'label' => 'Open'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'satisfied', 'label' => 'Satisfied'],
                ['value' => 'overdue', 'label' => 'Overdue'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Obligations/Create', [
            'instruments' => Instrument::orderBy('source_title')->get(['id', 'source_title'])
                ->map(fn ($i) => ['value' => $i->id, 'label' => $i->source_title])
                ->values()
                ->toArray(),
        ]);
    }

    public function store(StoreObligationRequest $request): RedirectResponse
    {
        Obligation::create($request->validated());

        return redirect()->route('obligations.index')
            ->with('flash', ['type' => 'success', 'message' => 'Obligation created.']);
    }

    public function show(int $id): Response
    {
        $obligation = Obligation::with(['instrument.regulator', 'instrument.nature'])->findOrFail($id);

        return Inertia::render('Obligations/Show', ['obligation' => $obligation]);
    }

    public function edit(int $id): Response
    {
        $obligation = Obligation::findOrFail($id);

        return Inertia::render('Obligations/Edit', [
            'obligation' => $obligation,
            'instruments' => Instrument::orderBy('source_title')->get(['id', 'source_title'])
                ->map(fn ($i) => ['value' => $i->id, 'label' => $i->source_title])
                ->values()
                ->toArray(),
        ]);
    }

    public function update(UpdateObligationRequest $request, int $id): RedirectResponse
    {
        $obligation = Obligation::findOrFail($id);
        $obligation->update($request->validated());

        return redirect()->route('obligations.index')
            ->with('flash', ['type' => 'success', 'message' => 'Obligation updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $obligation = Obligation::findOrFail($id);
        $obligation->delete();

        return redirect()->route('obligations.index')
            ->with('flash', ['type' => 'success', 'message' => 'Obligation deleted.']);
    }

    private function mapStatus(string $dbStatus, ?string $dueDate): string
    {
        if ($dbStatus === 'overdue') {
            return 'overdue';
        }
        if ($dbStatus === 'satisfied') {
            return 'completed';
        }
        if ($dueDate !== null && $dueDate < now()->toDateString()) {
            return 'overdue';
        }
        if ($dueDate !== null && $dueDate <= now()->addDays(14)->toDateString()) {
            return 'medium';
        }

        return 'info';
    }
}
