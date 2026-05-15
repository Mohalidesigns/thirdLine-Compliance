<?php

declare(strict_types=1);

namespace Modules\Training\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Training\Models\AttestationCampaign;
use Modules\Training\Models\AttestationRecord;
use Modules\Training\Services\AttestationService;

class MyAttestationsController extends Controller
{
    public function __construct(
        private readonly AttestationService $service,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $this->authorize('sign', AttestationCampaign::class);

        $pending = $this->service->pendingForUser($user)->map(fn (AttestationCampaign $c) => [
            'id' => $c->id,
            'code' => $c->code,
            'title' => $c->title,
            'body_preview' => mb_substr(strip_tags($c->body), 0, 200),
            'ends_at' => $c->ends_at?->toIso8601String(),
            'days_remaining' => $c->ends_at !== null
                ? (int) now()->startOfDay()->diffInDays($c->ends_at->startOfDay(), false)
                : null,
            'is_overdue' => $c->ends_at !== null && $c->ends_at->isPast(),
        ]);

        // Campaigns the user has already signed.
        $signedRecords = AttestationRecord::with('campaign')
            ->where('user_id', $user->id)
            ->get();

        $signed = $signedRecords->map(fn (AttestationRecord $r) => [
            'id' => $r->campaign->id,
            'code' => $r->campaign->code,
            'title' => $r->campaign->title,
            'signed_at' => $r->signed_at?->toIso8601String(),
        ]);

        return Inertia::render('My/Attestations/Index', [
            'pending' => $pending,
            'signed' => $signed,
        ]);
    }

    public function show(int $campaignId): InertiaResponse
    {
        $campaign = AttestationCampaign::findOrFail($campaignId);
        $this->authorize('view', $campaign);

        $user = request()->user();
        $record = AttestationRecord::withoutGlobalScopes()
            ->where('campaign_id', $campaign->id)
            ->where('user_id', $user->id)
            ->first();

        return Inertia::render('My/Attestations/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'code' => $campaign->code,
                'title' => $campaign->title,
                'body' => $campaign->body,
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'ends_at' => $campaign->ends_at?->toIso8601String(),
            ],
            'already_signed' => $record !== null,
            'signed_at' => $record?->signed_at?->toIso8601String(),
            'can' => [
                'sign' => $user->can('sign', $campaign),
            ],
        ]);
    }

    public function sign(int $campaignId, Request $request): RedirectResponse
    {
        $campaign = AttestationCampaign::findOrFail($campaignId);
        $this->authorize('sign', $campaign);

        $request->validate([
            'confirmation' => ['required', 'accepted'],
        ]);

        if ($campaign->status !== 'active') {
            abort(422, 'This campaign is no longer accepting signatures.');
        }

        $this->service->signCampaign($campaign, $request->user(), $request);

        return redirect()->route('my.attestations.index')
            ->with('flash', ['type' => 'success', 'message' => 'Attestation recorded successfully.']);
    }
}
