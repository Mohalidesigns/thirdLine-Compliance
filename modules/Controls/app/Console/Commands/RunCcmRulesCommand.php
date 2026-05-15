<?php

declare(strict_types=1);

namespace Modules\Controls\Console\Commands;

use App\Services\AuditWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Controls\Ccm\Rules\OverdueObligationsCcmRule;
use Modules\Controls\Ccm\Rules\StalePoliciesCcmRule;
use Modules\Controls\Contracts\CcmRule;
use Modules\Controls\Models\CcmRuleRun;
use Modules\Controls\Models\Issue;
use Modules\Library\Models\Obligation;
use Modules\Policy\Models\Policy;

class RunCcmRulesCommand extends Command
{
    protected $signature = 'controls:run-ccm {--tenant= : Restrict execution to a single tenant ID}';

    protected $description = 'Run all registered CCM rules per tenant, log results, and create Issues on threshold breaches.';

    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');

        $tenantIds = $tenantFilter !== null
            ? [(int) $tenantFilter]
            : $this->resolveTenantIds();

        foreach ($tenantIds as $tenantId) {
            $this->line("Running CCM rules for tenant {$tenantId}…");
            $this->runForTenant($tenantId);
        }

        $this->info('CCM rules completed.');

        return Command::SUCCESS;
    }

    private function runForTenant(int $tenantId): void
    {
        // Bind the current tenant so BelongsToTenant global scope picks it up.
        app()->bind('current.tenant_id', fn () => $tenantId);

        $rules = [
            new OverdueObligationsCcmRule,
            new StalePoliciesCcmRule,
        ];

        foreach ($rules as $rule) {
            $this->runRule($rule, $tenantId);
        }
    }

    private function runRule(CcmRule $rule, int $tenantId): void
    {
        try {
            $result = $rule->evaluate();
            $status = $result['breached'] ? 'threshold_breached' : 'passed';

            $run = CcmRuleRun::create([
                'rule_name' => $rule->name(),
                'run_at' => now(),
                'status' => $status,
                'metric_value' => $result['metric_value'],
                'threshold' => $rule->threshold(),
                'details' => ['kci_name' => $rule->kciName(), 'cadence' => $rule->cadence(), 'tenant_id' => $tenantId],
                'issue_id' => null,
            ]);

            if ($result['breached']) {
                $breaches = $result['breaches'] ?? [];

                // Derive the highest severity from individual breach records, falling
                // back to 'medium' when no per-item severities are available.
                $severity = $this->highestSeverity($breaches);

                // Derive linked FK from the first breach subject that maps to a known type.
                $linkedIds = $this->resolveLinkedIds($breaches);

                $issue = Issue::create(array_merge([
                    'source_type' => 'ccm_rule',
                    'source_id' => $run->id,
                    'title' => "CCM threshold breached: {$rule->kciName()}",
                    'description' => "Rule '{$rule->name()}' detected {$result['metric_value']} item(s) above threshold of {$rule->threshold()}.",
                    'severity' => $severity,
                    'status' => 'open',
                    'due_date' => now()->addDays(14)->toDateString(),
                ], $linkedIds));

                $run->issue_id = $issue->id;
                $run->save();

                $this->auditWriter->record(
                    action: 'ccm.threshold_breached',
                    subject: $run,
                    context: [
                        'rule_name' => $rule->name(),
                        'kci_name' => $rule->kciName(),
                        'metric_value' => $result['metric_value'],
                        'threshold' => $rule->threshold(),
                        'issue_id' => $issue->id,
                        'tenant_id' => $tenantId,
                    ],
                );

                $this->warn("  BREACH: {$rule->kciName()} — value={$result['metric_value']}, threshold={$rule->threshold()}. Issue {$issue->reference} opened.");
            } else {
                $this->line("  PASS: {$rule->kciName()} — value={$result['metric_value']}");
            }
        } catch (\Throwable $e) {
            CcmRuleRun::create([
                'rule_name' => $rule->name(),
                'run_at' => now(),
                'status' => 'error',
                'metric_value' => null,
                'threshold' => $rule->threshold(),
                'details' => ['error' => $e->getMessage(), 'tenant_id' => $tenantId],
            ]);

            $this->error("  ERROR: {$rule->name()} — {$e->getMessage()}");
        }
    }

    /**
     * Determine the distinct tenant IDs to process.
     * In the MVP there is a single tenant (id=1); in a multi-tenant deployment,
     * query the users table for distinct tenant_id values as a lightweight proxy.
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

            // Always include the default tenant even if no users exist yet.
            if (! in_array(1, $ids, true)) {
                $ids[] = 1;
            }

            return $ids;
        } catch (\Throwable) {
            return [1];
        }
    }

    /**
     * Return the highest severity level among breach records.
     *
     * @param  list<array{severity: string, subject: array{type: string, id: int}, detail: string}>  $breaches
     */
    private function highestSeverity(array $breaches): string
    {
        $order = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $highest = 'medium';

        foreach ($breaches as $breach) {
            $s = $breach['severity'] ?? 'medium';
            if (($order[$s] ?? 0) > ($order[$highest] ?? 0)) {
                $highest = $s;
            }
        }

        return $highest;
    }

    /**
     * Extract the linked_*_id FK values from the first recognisable breach subject.
     *
     * @param  list<array{severity: string, subject: array{type: string, id: int}, detail: string}>  $breaches
     * @return array<string, int|null>
     */
    private function resolveLinkedIds(array $breaches): array
    {
        $linked = [
            'linked_control_id' => null,
            'linked_risk_id' => null,
            'linked_obligation_id' => null,
            'linked_policy_id' => null,
        ];

        foreach ($breaches as $breach) {
            $type = $breach['subject']['type'] ?? null;
            $id = $breach['subject']['id'] ?? null;

            if ($type === null || $id === null) {
                continue;
            }

            if (is_a($type, Obligation::class, true)) {
                $linked['linked_obligation_id'] = (int) $id;
                break;
            }

            if (is_a($type, Policy::class, true)) {
                $linked['linked_policy_id'] = (int) $id;
                break;
            }
        }

        // Remove null entries so they don't override model defaults unnecessarily.
        return array_filter($linked, fn ($v) => $v !== null);
    }
}
