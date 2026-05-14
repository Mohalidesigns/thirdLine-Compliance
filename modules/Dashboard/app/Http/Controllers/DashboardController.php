<?php

declare(strict_types=1);

namespace Modules\Dashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Library\Services\InstrumentService;
use Modules\Sanctkb\Services\SanctionService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly InstrumentService $instrumentService,
        private readonly SanctionService $sanctionService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'instruments' => $this->instrumentService->count(),
                'obligations' => $this->instrumentService->obligationCount(),
                'sanctions'   => $this->sanctionService->count(),
                'deadlines'   => $this->instrumentService->deadlineCount(),
            ],
            'byRegulator' => $this->instrumentService->countByRegulator(),
            'byNature'    => $this->instrumentService->countByNature(),
            'byRisk'      => $this->instrumentService->countByRisk(),
            'upcoming'    => $this->instrumentService->upcomingObligations(),
        ]);
    }
}
