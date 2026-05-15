<?php

declare(strict_types=1);

namespace Modules\Training\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\CertificationService;
use Modules\Training\Services\TrainingService;

class TrainingCheckOverdueCommand extends Command
{
    protected $signature = 'training:check-overdue';

    protected $description = 'Mark overdue training enrollments and refresh certification expiry statuses.';

    public function __construct(
        private readonly TrainingService $trainingService,
        private readonly CertificationService $certificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantIds = $this->resolveTenantIds();

        foreach ($tenantIds as $tenantId) {
            $this->line("Processing tenant {$tenantId}…");
            $this->processOverdueEnrollmentsForTenant($tenantId);
        }

        // Certification expiry scan is global (not tenant-scoped per-run).
        $changed = $this->certificationService->scanForExpiring();
        $this->line("Certification expiry scan: {$changed} status change(s).");

        $this->info('training:check-overdue completed.');

        return Command::SUCCESS;
    }

    private function processOverdueEnrollmentsForTenant(int $tenantId): void
    {
        // Bind the current tenant so BelongsToTenant global scope picks it up.
        app()->bind('current.tenant_id', fn () => $tenantId);

        $overdueEnrollments = TrainingEnrollment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['enrolled', 'in_progress'])
            ->where('due_at', '<', now())
            ->get();

        $count = 0;

        foreach ($overdueEnrollments as $enrollment) {
            $this->trainingService->markOverdue($enrollment);
            $count++;
        }

        $this->line("  Tenant {$tenantId}: marked {$count} enrollment(s) overdue.");
    }

    /**
     * Determine the distinct tenant IDs to process.
     * Falls back gracefully to [1] when the users.tenant_id column does not exist.
     *
     * @return list<int>
     */
    private function resolveTenantIds(): array
    {
        try {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                return [1];
            }

            $ids = DB::table('users')
                ->whereNotNull('tenant_id')
                ->distinct()
                ->pluck('tenant_id')
                ->map(fn ($v) => (int) $v)
                ->toArray();

            if (! in_array(1, $ids, true)) {
                $ids[] = 1;
            }

            return $ids;
        } catch (\Throwable) {
            return [1];
        }
    }
}
