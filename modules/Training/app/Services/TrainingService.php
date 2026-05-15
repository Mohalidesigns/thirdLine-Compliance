<?php

declare(strict_types=1);

namespace Modules\Training\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\Training;
use Modules\Training\Models\TrainingEnrollment;

class TrainingService
{
    /**
     * Enroll a user in a training. Idempotent — if an active enrollment exists, return it.
     *
     * An "active" enrollment is one that is not exempted (all other statuses count as active).
     */
    public function enrollUser(Training $training, User $user, ?int $enrolledById = null): TrainingEnrollment
    {
        $existing = TrainingEnrollment::withoutGlobalScopes()
            ->where('training_id', $training->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return TrainingEnrollment::create([
            'training_id' => $training->id,
            'user_id' => $user->id,
            'enrolled_at' => now(),
            'due_at' => now()->addDays($training->sla_days),
            'status' => 'enrolled',
            'enrolled_by' => $enrolledById,
        ]);
    }

    /**
     * Enroll multiple users in a training. Returns the count of new enrollments created.
     */
    public function bulkEnroll(Training $training, Collection $users, ?int $enrolledById = null): int
    {
        $created = 0;

        foreach ($users as $user) {
            $existing = TrainingEnrollment::withoutGlobalScopes()
                ->where('training_id', $training->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing === null) {
                TrainingEnrollment::create([
                    'training_id' => $training->id,
                    'user_id' => $user->id,
                    'enrolled_at' => now(),
                    'due_at' => now()->addDays($training->sla_days),
                    'status' => 'enrolled',
                    'enrolled_by' => $enrolledById,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Auto-enroll a user in all mandatory trainings that match their roles.
     *
     * Target roles: if target_roles is empty/null, the training applies to all users.
     * Otherwise, it applies to users who have at least one matching role.
     *
     * Returns the count of new enrollments created.
     */
    public function autoEnrollMandatoryForUser(User $user, ?int $enrolledById = null): int
    {
        $userRoles = $user->getRoleNames()->toArray();

        $trainings = Training::where('is_mandatory', true)->get();

        $created = 0;

        foreach ($trainings as $training) {
            $targetRoles = $training->target_roles ?? [];

            // Empty target_roles means the training applies to all users.
            $applies = empty($targetRoles) || count(array_intersect($userRoles, $targetRoles)) > 0;

            if (! $applies) {
                continue;
            }

            $existing = TrainingEnrollment::withoutGlobalScopes()
                ->where('training_id', $training->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing === null) {
                TrainingEnrollment::create([
                    'training_id' => $training->id,
                    'user_id' => $user->id,
                    'enrolled_at' => now(),
                    'due_at' => now()->addDays($training->sla_days),
                    'status' => 'enrolled',
                    'enrolled_by' => $enrolledById,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Transition an enrollment to in_progress.
     */
    public function markStarted(TrainingEnrollment $enrollment): TrainingEnrollment
    {
        if (! in_array($enrollment->status, ['enrolled', 'overdue'], true)) {
            return $enrollment;
        }

        $enrollment->update([
            'status' => 'in_progress',
            'started_at' => $enrollment->started_at ?? now(),
        ]);

        return $enrollment;
    }

    /**
     * Transition an enrollment to completed.
     */
    public function markCompleted(TrainingEnrollment $enrollment, ?int $score, ?int $completedById): TrainingEnrollment
    {
        $enrollment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score' => $score,
            'completed_by' => $completedById,
            'started_at' => $enrollment->started_at ?? now(),
        ]);

        return $enrollment;
    }

    /**
     * Grant an exemption on an enrollment.
     */
    public function grantExemption(TrainingEnrollment $enrollment, string $reason, int $byId): TrainingEnrollment
    {
        $enrollment->update([
            'status' => 'exempted',
            'exempted_at' => now(),
            'exemption_reason' => $reason,
            'completed_by' => $byId,
        ]);

        return $enrollment;
    }

    /**
     * Mark an enrollment as overdue. Only called by the daily command.
     */
    public function markOverdue(TrainingEnrollment $enrollment): TrainingEnrollment
    {
        $enrollment->update(['status' => 'overdue']);

        return $enrollment;
    }

    /**
     * Compute completion statistics for a given tenant.
     *
     * @return array{
     *   mandatory_total: int,
     *   mandatory_completed: int,
     *   completion_rate: float,
     *   by_category: array<string, float>,
     *   by_role: array<string, float>
     * }
     */
    public function completionStats(int $tenantId): array
    {
        // Mandatory enrollments for this tenant.
        $mandatoryEnrollments = DB::table('training_enrollments as te')
            ->join('trainings as t', 't.id', '=', 'te.training_id')
            ->where('te.tenant_id', $tenantId)
            ->where('t.is_mandatory', true)
            ->whereNull('t.deleted_at')
            ->select('te.status')
            ->get();

        $mandatoryTotal = $mandatoryEnrollments->count();
        $mandatoryCompleted = $mandatoryEnrollments->where('status', 'completed')->count();
        $completionRate = $mandatoryTotal > 0
            ? round($mandatoryCompleted / $mandatoryTotal, 4)
            : 0.0;

        // By category.
        $categoryRows = DB::table('training_enrollments as te')
            ->join('trainings as t', 't.id', '=', 'te.training_id')
            ->where('te.tenant_id', $tenantId)
            ->whereNull('t.deleted_at')
            ->select('t.category', 'te.status')
            ->get();

        $byCategory = [];
        foreach ($categoryRows->groupBy('category') as $category => $rows) {
            $total = $rows->count();
            $completed = $rows->where('status', 'completed')->count();
            $byCategory[$category] = $total > 0 ? round($completed / $total, 4) : 0.0;
        }

        // By role — look at the roles of users who have enrollments.
        $userEnrollments = DB::table('training_enrollments as te')
            ->where('te.tenant_id', $tenantId)
            ->select('te.user_id', 'te.status')
            ->get();

        $byRole = [];
        if ($userEnrollments->isNotEmpty()) {
            $userIds = $userEnrollments->pluck('user_id')->unique()->values();

            // Fetch user → roles mapping via spatie model_has_roles.
            $userRoleRows = DB::table('model_has_roles as mhr')
                ->join('roles as r', 'r.id', '=', 'mhr.role_id')
                ->whereIn('mhr.model_id', $userIds)
                ->where('mhr.model_type', 'App\\Models\\User')
                ->select('mhr.model_id as user_id', 'r.name as role_name')
                ->get();

            // Build user_id → [roles] map.
            $userRoles = [];
            foreach ($userRoleRows as $row) {
                $userRoles[$row->user_id][] = $row->role_name;
            }

            // Group enrollments by role.
            $roleEnrollments = [];
            foreach ($userEnrollments as $row) {
                $roles = $userRoles[$row->user_id] ?? ['no_role'];
                foreach ($roles as $role) {
                    $roleEnrollments[$role][] = $row->status;
                }
            }

            foreach ($roleEnrollments as $role => $statuses) {
                $total = count($statuses);
                $completed = count(array_filter($statuses, fn ($s) => $s === 'completed'));
                $byRole[$role] = $total > 0 ? round($completed / $total, 4) : 0.0;
            }
        }

        return [
            'mandatory_total' => $mandatoryTotal,
            'mandatory_completed' => $mandatoryCompleted,
            'completion_rate' => $completionRate,
            'by_category' => $byCategory,
            'by_role' => $byRole,
        ];
    }
}
