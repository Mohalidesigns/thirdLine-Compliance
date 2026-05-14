<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Policy\Models\Policy;
use Modules\Policy\Models\PolicyVersion;
use Modules\Policy\States\Policy\InForce;
use Modules\Policy\States\Policy\UnderReview;

class AdvancePolicyStatesCommand extends Command
{
    protected $signature = 'policy:advance-states';

    protected $description = 'Advances policy states based on dates: Published→InForce, InForce→UnderReview';

    public function handle(): int
    {
        $advancedToForce = 0;
        $advancedToReview = 0;

        Policy::where('state', 'published')
            ->whereNotNull('effective_date')
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->chunkById(100, function ($policies) use (&$advancedToForce): void {
                foreach ($policies as $policy) {
                    DB::transaction(function () use ($policy, &$advancedToForce): void {
                        $policy->state->transitionTo(InForce::class);
                        $policy->saveQuietly();

                        PolicyVersion::create([
                            'policy_id' => $policy->id,
                            'version' => $policy->version,
                            'state' => 'in_force',
                            'body_snapshot' => $policy->body,
                            'published_pdf_path' => $policy->published_pdf_path,
                            'transitioned_by' => null,
                            'transitioned_at' => now(),
                            'transition_note' => 'Auto-advanced: effective_date reached',
                        ]);

                        $advancedToForce++;
                    });
                }
            });

        $reviewThreshold = now()->addDays(60)->toDateString();

        Policy::where('state', 'in_force')
            ->whereNotNull('next_review_date')
            ->whereDate('next_review_date', '<=', $reviewThreshold)
            ->chunkById(100, function ($policies) use (&$advancedToReview): void {
                foreach ($policies as $policy) {
                    DB::transaction(function () use ($policy, &$advancedToReview): void {
                        $policy->state->transitionTo(UnderReview::class);
                        $policy->saveQuietly();

                        PolicyVersion::create([
                            'policy_id' => $policy->id,
                            'version' => $policy->version,
                            'state' => 'under_review',
                            'body_snapshot' => $policy->body,
                            'published_pdf_path' => $policy->published_pdf_path,
                            'transitioned_by' => null,
                            'transitioned_at' => now(),
                            'transition_note' => 'Auto-advanced: next_review_date within 60 days',
                        ]);

                        $advancedToReview++;
                    });
                }
            });

        $this->info("Advanced {$advancedToForce} policies to In Force.");
        $this->info("Advanced {$advancedToReview} policies to Under Review.");

        return self::SUCCESS;
    }
}
