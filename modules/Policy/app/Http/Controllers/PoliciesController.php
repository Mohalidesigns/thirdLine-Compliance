<?php

declare(strict_types=1);

namespace Modules\Policy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Policy\Http\Requests\StorePolicyRequest;
use Modules\Policy\Http\Requests\TransitionPolicyRequest;
use Modules\Policy\Http\Requests\UpdatePolicyRequest;
use Modules\Policy\Models\Policy;
use Modules\Policy\Services\PolicyService;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

class PoliciesController extends Controller
{
    public function __construct(
        private readonly PolicyService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize('viewAny', Policy::class);

        $filters = $request->only(['search', 'status', 'category']);
        $paginated = $this->service->paginatedList($filters);

        $policies = $paginated->through(fn (Policy $p) => [
            'id' => $p->id,
            'reference' => $p->reference,
            'title' => $p->title,
            'category' => $p->category,
            'category_label' => $p->categoryLabel(),
            'owner_team' => $p->owner_team,
            'version' => $p->version,
            'state' => $p->state::$name,
            'state_label' => $p->stateLabel(),
            'state_color' => $p->stateColor(),
            'effective_date' => $p->effective_date?->format('Y-m-d'),
            'next_review_date' => $p->next_review_date?->format('Y-m-d'),
            'created_at' => $p->created_at?->toIso8601String(),
            'updated_at' => $p->updated_at?->toIso8601String(),
        ]);

        return Inertia::render('Policies/Index', [
            'policies' => $policies,
            'filters' => $filters,
            'statuses' => $this->stateOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize('create', Policy::class);

        return Inertia::render('Policies/Create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StorePolicyRequest $request): RedirectResponse
    {
        $this->authorize('create', Policy::class);

        $policy = $this->service->create($request->validated());

        return redirect()->route('policies.show', $policy->id)
            ->with('flash', ['type' => 'success', 'message' => 'Policy created.']);
    }

    public function show(int $id): InertiaResponse
    {
        $policy = $this->service->find($id);
        $this->authorize('view', $policy);

        $policy->loadMissing([
            'versions' => fn ($q) => $q->with('transitionedBy:id,name')->orderByDesc('transitioned_at')->limit(10),
        ]);

        $actorId = auth()->id();

        return Inertia::render('Policies/Show', [
            'policy' => array_merge($this->formatPolicy($policy), [
                'versions' => $policy->versions->map(fn ($v) => [
                    'id' => $v->id,
                    'version' => $v->version,
                    'state' => $v->state,
                    'state_label' => $this->stateLabelFromName($v->state),
                    'transitioned_at' => $v->transitioned_at?->toIso8601String(),
                    'transition_note' => $v->transition_note,
                    'transitioned_by' => $v->transitioned_by,
                    'transitioned_by_name' => $v->transitionedBy?->name,
                ])->toArray(),
                'acknowledgements_count' => $policy->acknowledgements()->count(),
            ]),
            'allowed_transitions' => $policy->allowedTransitions(),
            'can' => [
                'edit' => $policy->state::$name === 'draft' && auth()->user()?->can('update', $policy),
                'transition' => auth()->user()?->can('policies.transition.approve') || auth()->user()?->can('policies.transition.submit_for_review'),
                'delete' => $policy->state::$name === 'draft' && auth()->user()?->can('delete', $policy),
                'acknowledge' => in_array($policy->state::$name, ['published', 'in_force']),
                'download' => in_array($policy->state::$name, ['in_force', 'superseded']) && $policy->published_pdf_path !== null,
            ],
            'has_acknowledged' => $actorId !== null && $this->service->hasAcknowledged($policy, $actorId),
        ]);
    }

    public function edit(int $id): InertiaResponse
    {
        $policy = $this->service->find($id);
        $this->authorize('update', $policy);

        if ($policy->state::$name !== 'draft') {
            abort(403, 'Only draft policies can be edited.');
        }

        return Inertia::render('Policies/Edit', [
            'policy' => $this->formatPolicy($policy),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdatePolicyRequest $request, int $id): RedirectResponse
    {
        $policy = $this->service->find($id);
        $this->authorize('update', $policy);

        if ($policy->state::$name !== 'draft') {
            abort(403, 'Only draft policies can be updated.');
        }

        $this->service->update($policy, $request->validated());

        return redirect()->route('policies.show', $policy->id)
            ->with('flash', ['type' => 'success', 'message' => 'Policy updated.']);
    }

    public function transition(TransitionPolicyRequest $request, int $id): RedirectResponse
    {
        $policy = $this->service->find($id);
        $to = $request->validated('to');

        $this->authorize('transition', [$policy, $to]);

        $note = $request->validated('note');

        $requiresNote = collect($policy->allowedTransitions())
            ->where('to', $to)
            ->value('requires_note');

        if ($requiresNote && empty($note)) {
            return back()->withErrors(['note' => 'A note is required for this transition.']);
        }

        try {
            $this->service->transition($policy, $to, $note, auth()->id());
        } catch (TransitionNotFound $e) {
            return back()->withErrors(['transition' => 'This transition is not allowed from the current state.']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }

        return redirect()->route('policies.show', $policy->id)
            ->with('flash', ['type' => 'success', 'message' => 'Policy transitioned successfully.']);
    }

    public function destroy(int $id): RedirectResponse
    {
        $policy = $this->service->find($id);
        $this->authorize('delete', $policy);

        if ($policy->state::$name !== 'draft') {
            abort(403, 'Only draft policies can be deleted.');
        }

        $policy->delete();

        return redirect()->route('policies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Policy deleted.']);
    }

    public function download(int $id): Response
    {
        $policy = $this->service->find($id);
        $this->authorize('view', $policy);

        if (! in_array($policy->state::$name, ['in_force', 'superseded'])) {
            abort(404, 'PDF not available for this policy state.');
        }

        if (empty($policy->published_pdf_path)) {
            abort(404, 'PDF has not been generated yet.');
        }

        $absolutePath = storage_path('app/'.$policy->published_pdf_path);

        if (! file_exists($absolutePath)) {
            abort(404, 'PDF file not found.');
        }

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$policy->reference.'-v'.$policy->version.'.pdf"',
        ]);
    }

    public function acknowledge(int $id): RedirectResponse
    {
        $policy = $this->service->find($id);
        $this->authorize('view', $policy);

        $userId = auth()->id();

        if (! in_array($policy->state::$name, ['published', 'in_force'])) {
            abort(403, 'Policy must be published or in force to acknowledge.');
        }

        if ($this->service->hasAcknowledged($policy, $userId)) {
            return back()->withErrors(['acknowledgement' => 'You have already acknowledged this version.']);
        }

        $this->service->acknowledge($policy, $userId);

        return back()->with('flash', ['type' => 'success', 'message' => 'Policy acknowledged.']);
    }

    /** @return array<string, mixed> */
    private function formatPolicy(Policy $policy): array
    {
        return [
            'id' => $policy->id,
            'reference' => $policy->reference,
            'title' => $policy->title,
            'category' => $policy->category,
            'category_label' => $policy->categoryLabel(),
            'owner_team' => $policy->owner_team,
            'version' => $policy->version,
            'state' => $policy->state::$name,
            'state_label' => $policy->stateLabel(),
            'state_color' => $policy->stateColor(),
            'effective_date' => $policy->effective_date?->format('Y-m-d'),
            'next_review_date' => $policy->next_review_date?->format('Y-m-d'),
            'summary' => $policy->summary,
            'body' => $policy->body,
            'published_pdf_path' => $policy->published_pdf_path,
            'created_at' => $policy->created_at?->toIso8601String(),
            'updated_at' => $policy->updated_at?->toIso8601String(),
        ];
    }

    private function stateLabelFromName(?string $stateName): string
    {
        return match ($stateName) {
            'draft' => 'Draft',
            'in_review' => 'In Review',
            'approved' => 'Approved',
            'published' => 'Published',
            'in_force' => 'In Force',
            'under_review' => 'Under Review',
            'superseded' => 'Superseded',
            default => ucfirst((string) $stateName),
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    private function categoryOptions(): array
    {
        return [
            ['value' => 'aml', 'label' => 'AML & CFT'],
            ['value' => 'data_protection', 'label' => 'Data Protection'],
            ['value' => 'risk', 'label' => 'Risk Management'],
            ['value' => 'conduct', 'label' => 'Conduct'],
            ['value' => 'cyber', 'label' => 'Cybersecurity'],
            ['value' => 'governance', 'label' => 'Governance'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /** @return array<int, array{value: string, label: string, color: string}> */
    private function stateOptions(): array
    {
        return [
            ['value' => 'draft', 'label' => 'Draft', 'color' => 'gray'],
            ['value' => 'in_review', 'label' => 'In Review', 'color' => 'blue'],
            ['value' => 'approved', 'label' => 'Approved', 'color' => 'purple'],
            ['value' => 'published', 'label' => 'Published', 'color' => 'cyan'],
            ['value' => 'in_force', 'label' => 'In Force', 'color' => 'green'],
            ['value' => 'under_review', 'label' => 'Under Review', 'color' => 'orange'],
            ['value' => 'superseded', 'label' => 'Superseded', 'color' => 'gray'],
        ];
    }
}
