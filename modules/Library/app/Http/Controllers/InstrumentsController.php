<?php

declare(strict_types=1);

namespace Modules\Library\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AuditWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Library\Http\Requests\StoreInstrumentRequest;
use Modules\Library\Http\Requests\UpdateInstrumentRequest;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;
use Modules\Library\Services\InstrumentService;

class InstrumentsController extends Controller
{
    public function __construct(
        private readonly InstrumentService $service,
        private readonly AuditWriter $auditWriter,
    ) {}

    public function bulkImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $path = $request->file('file')->store('imports', 'local');

        $this->auditWriter->record('instrument.bulk_import_started', null, [
            'filename'          => $request->file('file')->getClientOriginalName(),
            'size_bytes'        => $request->file('file')->getSize(),
            'initiated_by'      => auth()->id(),
        ]);

        \Modules\Library\Jobs\BulkImportInstrumentsJob::dispatch($path);

        return redirect()->route('instruments.index')
            ->with('flash', ['type' => 'success', 'message' => 'Import queued. Instruments will appear shortly.']);
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'regulator', 'item_type', 'risk_rating']);
        $paginated = $this->service->paginatedList($filters);

        $instruments = $paginated->through(function (Instrument $i) {
            return [
                'id' => $i->id,
                'reference' => $i->regulator->code.'/'.$i->instrumentType->name.'/'
                    .str_pad((string) $i->id, 3, '0', STR_PAD_LEFT),
                'title' => $i->source_title,
                'regulator' => $i->regulator->code,
                'item_type' => $i->instrumentType->name,
                'risk_rating' => strtolower($i->riskRating->name),
                'effective_date' => $i->date_commence?->format('Y-m-d') ?? $i->date_issue?->format('Y-m-d') ?? '',
            ];
        });

        return Inertia::render('Instruments/Index', [
            'instruments' => $instruments,
            'filters' => $filters,
            'regulators' => Regulator::orderBy('code')
                ->get()
                ->map(fn ($r) => ['value' => $r->code, 'label' => $r->code])
                ->toArray(),
            'itemTypes' => InstrumentType::orderBy('name')
                ->get()
                ->map(fn ($t) => ['value' => $t->name, 'label' => $t->name])
                ->toArray(),
            'riskRatings' => RiskRating::orderBy('id')
                ->get()
                ->map(fn ($r) => ['value' => strtolower($r->name), 'label' => $r->name])
                ->toArray(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Instruments/Create', $this->formData());
    }

    public function store(StoreInstrumentRequest $request): RedirectResponse
    {
        Instrument::create($request->validated());

        return redirect()->route('instruments.index')
            ->with('flash', ['type' => 'success', 'message' => 'Instrument created.']);
    }

    public function show(int $id): Response
    {
        $instrument = $this->service->findInstrument($id);

        return Inertia::render('Instruments/Show', ['instrument' => $instrument]);
    }

    public function edit(int $id): Response
    {
        $instrument = $this->service->findInstrument($id);

        return Inertia::render('Instruments/Edit', array_merge(
            ['instrument' => $instrument],
            $this->formData(),
        ));
    }

    public function update(UpdateInstrumentRequest $request, int $id): RedirectResponse
    {
        $instrument = $this->service->findInstrument($id);
        $instrument->update($request->validated());

        return redirect()->route('instruments.index')
            ->with('flash', ['type' => 'success', 'message' => 'Instrument updated.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $instrument = $this->service->findInstrument($id);
        $instrument->delete();

        return redirect()->route('instruments.index')
            ->with('flash', ['type' => 'success', 'message' => 'Instrument deleted.']);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $toOptions = fn ($collection, string $labelField = 'name') => $collection
            ->map(fn ($row) => ['value' => $row->id, 'label' => $row->{$labelField}])
            ->values()
            ->toArray();

        return [
            'regulators' => Regulator::orderBy('name')->get(['id', 'code', 'name'])
                ->map(fn ($r) => ['value' => $r->id, 'label' => $r->code.' — '.$r->name])
                ->values()
                ->toArray(),
            'instrument_types' => $toOptions(InstrumentType::orderBy('name')->get(['id', 'name'])),
            'natures' => $toOptions(Nature::orderBy('name')->get(['id', 'name'])),
            'statuses' => $toOptions(Status::orderBy('name')->get(['id', 'name'])),
            'areas_of_focus' => $toOptions(AreaOfFocus::orderBy('name')->get(['id', 'name'])),
            'risk_ratings' => $toOptions(RiskRating::orderBy('id')->get(['id', 'name'])),
        ];
    }
}
