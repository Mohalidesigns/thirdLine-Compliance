<?php

declare(strict_types=1);

namespace Modules\Policy\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Policy\Jobs\RenderPolicyPdfJob;
use Modules\Policy\Models\Policy;
use Modules\Policy\Models\PolicyAcknowledgement;
use Modules\Policy\Models\PolicyVersion;
use Modules\Policy\States\Policy\Approved;
use Modules\Policy\States\Policy\Draft;
use Modules\Policy\States\Policy\InForce;
use Modules\Policy\States\Policy\InReview;
use Modules\Policy\States\Policy\Published;
use Modules\Policy\States\Policy\Superseded;
use Modules\Policy\States\Policy\UnderReview;

class PolicyService
{
    public function paginatedList(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Policy::query()->orderByDesc('updated_at');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term): void {
                $likeOp = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $q->where('title', $likeOp, "%{$term}%")
                    ->orWhere('reference', $likeOp, "%{$term}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('state', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function find(int $id): Policy
    {
        return Policy::findOrFail($id);
    }

    public function create(array $data): Policy
    {
        return Policy::create($data);
    }

    public function update(Policy $policy, array $data): Policy
    {
        $policy->update($data);

        return $policy;
    }

    public function transition(Policy $policy, string $to, ?string $note, ?int $actorId): Policy
    {
        $stateMap = [
            'draft' => Draft::class,
            'in_review' => InReview::class,
            'approved' => Approved::class,
            'published' => Published::class,
            'in_force' => InForce::class,
            'under_review' => UnderReview::class,
            'superseded' => Superseded::class,
        ];

        if (! isset($stateMap[$to])) {
            throw new \InvalidArgumentException("Unknown target state: {$to}");
        }

        $toClass = $stateMap[$to];

        DB::transaction(function () use ($policy, $toClass, $to, $note, $actorId): void {
            $previousState = $policy->state::$name;

            $policy->state->transitionTo($toClass);

            if ($to === 'published') {
                if (empty($policy->effective_date)) {
                    $policy->effective_date = now()->toDateString();
                }
            }

            $policy->save();

            PolicyVersion::create([
                'policy_id' => $policy->id,
                'version' => $policy->version,
                'state' => $to,
                'body_snapshot' => $policy->body,
                'published_pdf_path' => $policy->published_pdf_path,
                'transitioned_by' => $actorId,
                'transitioned_at' => now(),
                'transition_note' => $note,
            ]);

            if ($to === 'published') {
                RenderPolicyPdfJob::dispatch($policy->id);
            }
        });

        return $policy->fresh();
    }

    public function acknowledge(Policy $policy, int $userId): PolicyAcknowledgement
    {
        return PolicyAcknowledgement::create([
            'policy_id' => $policy->id,
            'user_id' => $userId,
            'acknowledged_at' => now(),
            'policy_version' => $policy->version,
        ]);
    }

    public function hasAcknowledged(Policy $policy, int $userId): bool
    {
        return PolicyAcknowledgement::where('policy_id', $policy->id)
            ->where('user_id', $userId)
            ->where('policy_version', $policy->version)
            ->exists();
    }

    public function count(): int
    {
        return Policy::count();
    }

    public function activePoliciesCount(): int
    {
        return Policy::whereIn('state', ['published', 'in_force'])->count();
    }
}
