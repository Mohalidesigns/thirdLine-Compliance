<?php

declare(strict_types=1);

namespace Modules\Returns\Console\Commands;

use Illuminate\Console\Command;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsSchedulerService;

class ReturnsScheduleCommand extends Command
{
    protected $signature = 'returns:schedule';

    protected $description = 'Schedule upcoming return runs, flag late runs, and dispatch due reminders.';

    public function __construct(
        private readonly ReturnsSchedulerService $schedulerService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // MVP: single-tenant; iterate all known tenant IDs.
        // When multi-tenancy is real, replace with a Tenant model query.
        $tenantId = ReturnRun::currentTenantId();

        $created = $this->schedulerService->scheduleAllUpcoming($tenantId);
        $this->info("Scheduled {$created} new return run(s).");

        $lateCount = $this->schedulerService->scanForLate($tenantId);
        $this->info("Flagged {$lateCount} run(s) as late.");

        $reminderCount = $this->schedulerService->dispatchDueReminders();
        $this->info("Fired {$reminderCount} due reminder(s).");

        return self::SUCCESS;
    }
}
