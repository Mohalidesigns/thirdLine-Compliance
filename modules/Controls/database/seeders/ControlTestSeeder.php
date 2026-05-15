<?php

declare(strict_types=1);

namespace Modules\Controls\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\ControlTest;
use Modules\Controls\Models\Issue;
use Modules\Controls\Services\SampleSizeCalculator;

class ControlTestSeeder extends Seeder
{
    public function run(): void
    {
        $calculator = app(SampleSizeCalculator::class);
        $userId = DB::table('users')->value('id') ?? 1;
        $controls = Control::all();

        if ($controls->isEmpty()) {
            return;
        }

        $outcomes = ['passed', 'passed', 'passed', 'partial', 'failed'];
        $testCount = 0;
        $targetTests = 30;

        while ($testCount < $targetTests) {
            foreach ($controls as $control) {
                if ($testCount >= $targetTests) {
                    break;
                }

                if ($control->status === 'draft') {
                    continue;
                }

                $populationSize = rand(50, 500);
                $sampleSize = $calculator->calculate($populationSize);
                $outcome = $outcomes[array_rand($outcomes)];
                $testedAt = now()->subDays(rand(5, 90))->subHours(rand(0, 10));

                $test = ControlTest::create([
                    'tenant_id' => 1,
                    'control_id' => $control->id,
                    'tested_by' => $userId,
                    'tested_at' => $testedAt,
                    'period_start' => $testedAt->copy()->subDays(30)->toDateString(),
                    'period_end' => $testedAt->copy()->toDateString(),
                    'sample_size' => $sampleSize,
                    'population_size' => $populationSize,
                    'confidence_level' => 0.95,
                    'outcome' => $outcome,
                    'findings' => $outcome === 'failed'
                        ? "Control failure detected during testing period. {$control->title} did not operate effectively for {$sampleSize} items reviewed."
                        : ($outcome === 'partial' ? "Minor deviations noted but overall control effective. Follow-up recommended." : null),
                ]);

                if ($outcome === 'failed') {
                    $maxIssueId = Issue::withoutGlobalScopes()->max('id') ?? 0;
                    Issue::create([
                        'tenant_id' => 1,
                        'reference' => 'ISS-'.str_pad((string) ($maxIssueId + 1), 4, '0', STR_PAD_LEFT),
                        'source_type' => 'control_test',
                        'source_id' => $test->id,
                        'title' => "Control test failure: {$control->title}",
                        'description' => "Control '{$control->reference}: {$control->title}' failed testing. {$test->findings}",
                        'severity' => in_array($control->frequency, ['continuous', 'daily']) ? 'high' : 'medium',
                        'status' => 'open',
                        'owner_team' => $control->owner_team,
                        'linked_control_id' => $control->id,
                        'due_date' => now()->addDays(30)->toDateString(),
                    ]);
                }

                $testCount++;
            }
        }
    }
}
