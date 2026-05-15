<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsService;

class ReturnsDashboardController extends Controller
{
    public function __construct(
        private readonly ReturnsService $service,
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize('viewAny', ReturnRun::class);

        $user = auth()->user();
        $tenantId = ReturnRun::currentTenantId();
        $stats = $this->service->dashboardStats($tenantId);

        // Upcoming runs: due in next 90 days, not yet submitted/acknowledged
        $upcomingRuns = ReturnRun::query()
            ->with('definition:id,code,title,regulator')
            ->whereIn('status', ['scheduled', 'in_progress', 'late'])
            ->where('due_at', '<=', now()->addDays(90))
            ->orderBy('due_at')
            ->limit(20)
            ->get()
            ->map(fn (ReturnRun $r) => [
                'id' => $r->id,
                'code' => $r->definition->code,
                'title' => $r->definition->title,
                'regulator' => $r->definition->regulator,
                'regulator_label' => $r->definition->regulatorLabel(),
                'period_label' => $r->period_label,
                'due_at' => $r->due_at->toIso8601String(),
                'days_until_due' => $r->daysUntilDue(),
                'status' => $r->status,
                'status_label' => $r->statusLabel(),
                'status_color' => $r->statusColor(),
            ])
            ->toArray();

        // Recent submissions
        $recentSubmissions = ReturnRun::query()
            ->with('definition:id,code,title,regulator')
            ->whereIn('status', ['submitted_pending_ack', 'acknowledged'])
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(15)
            ->get()
            ->map(fn (ReturnRun $r) => [
                'id' => $r->id,
                'code' => $r->definition->code,
                'title' => $r->definition->title,
                'period_label' => $r->period_label,
                'submitted_at' => $r->submitted_at?->toIso8601String(),
                'submission_reference' => $r->submission_reference,
                'regulator_label' => $r->definition->regulatorLabel(),
                'acknowledged' => $r->status === 'acknowledged',
            ])
            ->toArray();

        return Inertia::render('Returns/Dashboard', [
            'stats' => [
                'total_active_definitions' => $stats['total_active_definitions'],
                'runs_due_this_month' => $stats['runs_due_this_month'],
                'runs_submitted_this_month' => $stats['runs_submitted_this_month'],
                'runs_acknowledged_this_month' => $stats['runs_acknowledged_this_month'],
                'runs_late' => $stats['runs_late'],
                'on_time_rate_30d' => $stats['on_time_rate_30d'],
            ],
            'by_regulator' => $stats['by_regulator'],
            'upcoming_runs' => $upcomingRuns,
            'recent_submissions' => $recentSubmissions,
            'can' => [
                'view_runs' => $user?->can('returns.view') ?? false,
                'manage_definitions' => $user?->can('returns.manage') ?? false,
            ],
        ]);
    }
}
