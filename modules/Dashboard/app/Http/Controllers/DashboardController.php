<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\Issue;
use Modules\Incident\Models\Incident;
use Modules\Incident\Services\IncidentService;
use Modules\Library\Services\InstrumentService;
use Modules\Rcsa\Models\Risk;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsService;
use Modules\Sanctkb\Services\SanctionService;
use Modules\Training\Models\AttestationCampaign;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\AttestationService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly InstrumentService $instrumentService,
        private readonly SanctionService $sanctionService,
        private readonly IncidentService $incidentService,
        private readonly ReturnsService $returnsService,
        private readonly AttestationService $attestationService,
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // -----------------------------------------------------------------------
        // Row 1 — Phase-1 stats (always visible; no permission gate needed)
        // -----------------------------------------------------------------------
        $stats = [
            'instruments' => $this->instrumentService->count(),
            'obligations' => $this->instrumentService->obligationCount(),
            'sanctions'   => $this->sanctionService->count(),
            'deadlines'   => $this->instrumentService->deadlineCount(),
        ];

        // -----------------------------------------------------------------------
        // Row 2 — GRC module stats (permission-gated: null when not permitted)
        // -----------------------------------------------------------------------
        $openRisks = $user->can('risks.view')
            ? Risk::whereIn('residual_rating', ['high', 'critical'])
                ->orWhereNull('residual_rating')
                ->count()
            : null;

        $openControls = $user->can('controls.view')
            ? Control::where('status', 'active')->count()
            : null;

        $openIssues = $user->can('issues.view')
            ? Issue::where('status', 'open')->count()
            : null;

        $incidentStats = $user->can('incidents.view')
            ? $this->incidentService->stats()
            : null;

        $openIncidents = $incidentStats !== null ? $incidentStats['total_open'] : null;

        // -----------------------------------------------------------------------
        // Row 3 — Operations stats (permission-gated)
        // -----------------------------------------------------------------------
        $tenantId = 1; // MVP single-tenant stub

        $returnsDue = $user->can('returns.dashboard')
            ? ReturnRun::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['scheduled', 'in_progress', 'late'])
                ->whereBetween('due_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count()
            : null;

        $mandatoryOutstanding = $user->can('training.view')
            ? TrainingEnrollment::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['enrolled', 'overdue', 'in_progress'])
                ->count()
            : null;

        $pendingAttestations = $user->can('attestations.sign')
            ? $this->attestationService->pendingForUser($user)->count()
            : null;

        $openCapaActions = $user->can('issues.view')
            ? Issue::where('status', 'open')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count()
            : null;

        // -----------------------------------------------------------------------
        // Recent incidents widget (last 5, for users with incidents.view)
        // -----------------------------------------------------------------------
        $recentIncidents = null;
        if ($user->can('incidents.view')) {
            $recentIncidents = Incident::query()
                ->orderByDesc('detected_at')
                ->limit(5)
                ->get(['id', 'code', 'title', 'status', 'severity', 'detected_at'])
                ->map(fn ($incident) => [
                    'id'          => $incident->id,
                    'code'        => $incident->code,
                    'title'       => $incident->title,
                    'status'      => $incident->status::$name,
                    'severity'    => $incident->severity,
                    'detected_at' => $incident->detected_at?->toDateString(),
                ]);
        }

        // -----------------------------------------------------------------------
        // Outstanding items widget — personal context for the authenticated user
        // -----------------------------------------------------------------------
        $outstandingItems = [];

        if ($user->can('attestations.sign')) {
            foreach ($this->attestationService->pendingForUser($user) as $campaign) {
                $outstandingItems[] = [
                    'type'  => 'attestation',
                    'label' => "Sign attestation: {$campaign->title}",
                    'url'   => route('my.attestations.show', $campaign->id),
                ];
            }
        }

        if ($user->can('training.view')) {
            $overdueEnrollments = TrainingEnrollment::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('status', 'overdue')
                ->with('training:id,title')
                ->limit(3)
                ->get();

            foreach ($overdueEnrollments as $enrollment) {
                $outstandingItems[] = [
                    'type'  => 'training',
                    'label' => "Overdue training: {$enrollment->training?->title}",
                    'url'   => route('my.training.show', $enrollment->id),
                ];
            }
        }

        if ($user->can('returns.dashboard')) {
            $lateRuns = ReturnRun::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('status', 'late')
                ->with('definition:id,name')
                ->limit(3)
                ->get();

            foreach ($lateRuns as $run) {
                $outstandingItems[] = [
                    'type'  => 'return',
                    'label' => "Late return: {$run->definition?->name} ({$run->period_label})",
                    'url'   => route('returns.dashboard'),
                ];
            }
        }

        if ($user->can('incidents.view')) {
            $overdueNotifications = \Modules\Incident\Models\IncidentNotification::where('status', 'overdue')
                ->with('incident:id,code,title')
                ->limit(3)
                ->get();

            foreach ($overdueNotifications as $notification) {
                $incident = $notification->incident;
                $outstandingItems[] = [
                    'type'  => 'incident_notification',
                    'label' => "Overdue notification ({$notification->regulator}): {$incident?->code}",
                    'url'   => $incident ? route('incidents.show', $incident->id) : route('incidents.index'),
                ];
            }
        }

        return Inertia::render('Dashboard/Index', [
            // Row 1 (Phase-1)
            'stats' => $stats,

            // Row 2 (GRC)
            'grcStats' => [
                'open_risks'     => $openRisks,
                'open_controls'  => $openControls,
                'open_issues'    => $openIssues,
                'open_incidents' => $openIncidents,
            ],

            // Row 3 (Operations)
            'opsStats' => [
                'returns_due'           => $returnsDue,
                'mandatory_outstanding' => $mandatoryOutstanding,
                'pending_attestations'  => $pendingAttestations,
                'open_capa_actions'     => $openCapaActions,
            ],

            // Widgets
            'recentIncidents' => $recentIncidents,
            'outstandingItems' => $outstandingItems,

            // Legacy Phase-1 widgets (unchanged)
            'byRegulator' => $this->instrumentService->countByRegulator(),
            'byNature'    => $this->instrumentService->countByNature(),
            'byRisk'      => $this->instrumentService->countByRisk(),
            'upcoming'    => $this->instrumentService->upcomingObligations(),
        ]);
    }
}
