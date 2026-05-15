<?php

declare(strict_types=1);

namespace Modules\Controls\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Models\CcmRuleRun;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\ControlTest;
use Modules\Controls\Models\Issue;

class ControlsService
{
    public function paginatedControls(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Control::query()->orderBy('reference');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($term, $likeOp): void {
                $q->where('title', $likeOp, "%{$term}%")
                    ->orWhere('reference', $likeOp, "%{$term}%")
                    ->orWhere('owner_team', $likeOp, "%{$term}%");
            });
        }

        if (! empty($filters['type'])) {
            $query->where('control_type', $filters['type']);
        }

        if (! empty($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['owner'])) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('owner_team', $likeOp, "%{$filters['owner']}%");
        }

        if (! empty($filters['due_soon'])) {
            $query->where('status', 'active')
                ->where('next_test_due', '<=', now()->addDays(7)->toDateString());
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): Control
    {
        return Control::findOrFail($id);
    }

    public function create(array $data): Control
    {
        return Control::create($data);
    }

    public function update(Control $control, array $data): Control
    {
        $control->update($data);

        return $control;
    }

    public function controlsDueForTesting(): Collection
    {
        return Control::query()
            ->where('status', 'active')
            ->where('next_test_due', '<=', now()->addDays(7)->toDateString())
            ->orderBy('next_test_due')
            ->get();
    }

    public function recordTest(int $controlId, array $testData, int $userId): ControlTest
    {
        return DB::transaction(function () use ($controlId, $testData, $userId): ControlTest {
            $control = Control::findOrFail($controlId);

            $test = ControlTest::create(array_merge($testData, [
                'control_id' => $controlId,
                'tested_by' => $userId,
                'tested_at' => $testData['tested_at'] ?? now(),
            ]));

            $control->last_tested_at = $test->tested_at;
            $this->updateNextTestDue($control);
            $control->save();

            if ($test->outcome === 'failed') {
                Issue::create([
                    'source_type' => 'control_test',
                    'source_id' => $test->id,
                    'title' => "Control test failure: {$control->title}",
                    'description' => $test->findings ?? "Control '{$control->title}' failed testing. See test record {$test->id} for details.",
                    'severity' => $this->severityFromFrequency($control->frequency),
                    'status' => 'open',
                    'owner_team' => $control->owner_team,
                    'linked_control_id' => $control->id,
                    'due_date' => now()->addDays(30)->toDateString(),
                ]);
            }

            return $test;
        });
    }

    public function updateNextTestDue(Control $control): void
    {
        if ($control->last_tested_at === null) {
            return;
        }

        $interval = $control->frequencyInterval();
        $nextDue = Carbon::instance($control->last_tested_at)->add($interval);
        $control->next_test_due = $nextDue->toDateString();
    }

    public function paginatedIssues(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Issue::query()->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findIssue(int $id): Issue
    {
        return Issue::findOrFail($id);
    }

    public function paginatedTests(int $controlId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = ControlTest::query()
            ->where('control_id', $controlId)
            ->with('tester')
            ->orderByDesc('tested_at');

        if (! empty($filters['outcome'])) {
            $query->where('outcome', $filters['outcome']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('tested_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('tested_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findTest(int $id): ControlTest
    {
        return ControlTest::findOrFail($id);
    }

    public function kciSummary(): array
    {
        $lastRuns = CcmRuleRun::query()
            ->select(['rule_name', DB::raw('MAX(run_at) as last_run_at')])
            ->groupBy('rule_name')
            ->pluck('last_run_at', 'rule_name');

        $result = [];
        foreach ($lastRuns as $ruleName => $lastRunAt) {
            $run = CcmRuleRun::where('rule_name', $ruleName)
                ->where('run_at', $lastRunAt)
                ->first();

            if ($run !== null) {
                $result[] = [
                    'rule_name' => $ruleName,
                    'run_at' => $run->run_at,
                    'status' => $run->status,
                    'metric_value' => $run->metric_value,
                    'threshold' => $run->threshold,
                ];
            }
        }

        return $result;
    }

    public function dueSoonCount(): int
    {
        return Control::query()
            ->where('status', 'active')
            ->where('next_test_due', '<=', now()->addDays(7)->toDateString())
            ->count();
    }

    public function overdueCount(): int
    {
        return Control::query()
            ->where('status', 'active')
            ->where('next_test_due', '<', now()->toDateString())
            ->count();
    }

    private function severityFromFrequency(string $frequency): string
    {
        return match ($frequency) {
            'continuous', 'daily' => 'high',
            'weekly', 'monthly' => 'medium',
            default => 'low',
        };
    }
}
