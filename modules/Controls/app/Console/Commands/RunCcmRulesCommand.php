<?php

declare(strict_types=1);

namespace Modules\Controls\Console\Commands;

use App\Services\AuditWriter;
use Illuminate\Console\Command;
use Modules\Controls\Ccm\Rules\OverdueObligationsCcmRule;
use Modules\Controls\Ccm\Rules\StalePoliciesCcmRule;
use Modules\Controls\Contracts\CcmRule;
use Modules\Controls\Models\CcmRuleRun;
use Modules\Controls\Models\Issue;

class RunCcmRulesCommand extends Command
{
    protected $signature = 'controls:run-ccm';

    protected $description = 'Run all registered CCM rules, log results, and create Issues on threshold breaches.';

    /** @var CcmRule[] */
    private array $rules = [];

    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
        parent::__construct();

        $this->rules = [
            new OverdueObligationsCcmRule,
            new StalePoliciesCcmRule,
        ];
    }

    public function handle(): int
    {
        foreach ($this->rules as $rule) {
            $this->runRule($rule);
        }

        $this->info('CCM rules completed.');

        return Command::SUCCESS;
    }

    private function runRule(CcmRule $rule): void
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
                'details' => ['kci_name' => $rule->kciName(), 'cadence' => $rule->cadence()],
                'issue_id' => null,
            ]);

            if ($result['breached']) {
                $issue = Issue::create([
                    'source_type' => 'ccm_rule',
                    'source_id' => $run->id,
                    'title' => "CCM threshold breached: {$rule->kciName()}",
                    'description' => "Rule '{$rule->name()}' detected {$result['metric_value']} items above threshold of {$rule->threshold()}.",
                    'severity' => 'medium',
                    'status' => 'open',
                    'due_date' => now()->addDays(14)->toDateString(),
                ]);

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
                'details' => ['error' => $e->getMessage()],
            ]);

            $this->error("  ERROR: {$rule->name()} — {$e->getMessage()}");
        }
    }
}
