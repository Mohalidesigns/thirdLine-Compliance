<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds all roles and permissions for the Atheris Compliance RBAC model.
 *
 * Run order: this seeder must run BEFORE any module seeders so that
 * assignRole() calls in those seeders succeed.
 *
 * To add a new permission:
 *   1. Add it to the $permissions array below.
 *   2. Assign it to the appropriate roles in $rolePermissions.
 *   3. Create or update the Laravel Policy class that enforces the check.
 *
 * The seeder is idempotent — running it multiple times will not duplicate
 * rows because it uses firstOrCreate throughout.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * All permissions in the system, named <resource>.<verb>.
     *
     * @var list<string>
     */
    private array $permissions = [
        // Policies
        'policies.view',
        'policies.create',
        'policies.update',
        'policies.transition.submit_for_review',
        'policies.transition.approve',
        'policies.transition.publish',
        'policies.transition.force',
        'policies.delete',

        // Risk Assessment Cycles
        'cycles.view',
        'cycles.create',
        'cycles.update',
        'cycles.transition',
        'cycles.delete',

        // Risks
        'risks.view',
        'risks.create',
        'risks.update',
        'risks.score',
        'risks.delete',

        // Controls
        'controls.view',
        'controls.create',
        'controls.update',
        'controls.test',
        'controls.delete',

        // Issues
        'issues.view',
        'issues.create',
        'issues.update',
        'issues.transition',
        'issues.delete',

        // Audit log
        'audit.view',

        // Admin panel
        'admin.access',

        // Training (M15)
        'training.view',
        'training.manage',
        'training.complete',
        'attestations.manage',
        'attestations.sign',
        'certifications.view',
        'certifications.manage',

        // Incidents (M16)
        'incidents.view',
        'incidents.create',
        'incidents.update',
        'incidents.delete',
        'incidents.notify',
        'incidents.attach_evidence',
        'incidents.loss_register',

        // Returns (M10)
        'returns.view',
        'returns.manage',
        'returns.run',
        'returns.maker',
        'returns.checker',
        'returns.approver',
        'returns.acknowledge',
        'returns.dashboard',
    ];

    /**
     * Permission matrix per role.
     *
     * Note on policy_owner + policies.update:
     *   The permission is granted here. The PolicyPolicy authorizer further
     *   restricts the action to drafts the policy_owner created themselves.
     *
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'super_admin' => [
            // super_admin bypasses all checks via Gate::before — no explicit
            // list needed, but we assign admin.access so canAccessPanel works.
            'admin.access',
        ],

        'compliance_officer' => [
            'policies.view',
            'policies.create',
            'policies.update',
            'policies.transition.submit_for_review',
            'policies.transition.approve',
            'policies.transition.publish',
            'policies.transition.force',
            'policies.delete',

            'cycles.view',
            'cycles.create',
            'cycles.update',
            'cycles.transition',
            'cycles.delete',

            'risks.view',
            'risks.create',
            'risks.update',
            'risks.score',
            'risks.delete',

            'controls.view',
            'controls.create',
            'controls.update',
            'controls.test',
            'controls.delete',

            'issues.view',
            'issues.create',
            'issues.update',
            'issues.transition',
            'issues.delete',

            'audit.view',
            'admin.access',

            // Training (M15)
            'training.view',
            'training.manage',
            'training.complete',
            'attestations.manage',
            'attestations.sign',
            'certifications.view',
            'certifications.manage',

            // Incidents (M16)
            'incidents.view',
            'incidents.create',
            'incidents.update',
            'incidents.delete',
            'incidents.notify',
            'incidents.attach_evidence',
            'incidents.loss_register',

            // Returns (M10)
            'returns.view',
            'returns.manage',
            'returns.run',
            'returns.maker',
            'returns.checker',
            'returns.approver',
            'returns.acknowledge',
            'returns.dashboard',
        ],

        'risk_owner' => [
            'policies.view',

            'cycles.view',

            'risks.view',
            'risks.create',
            'risks.update',
            'risks.score',

            'controls.view',

            'issues.view',

            // Training (M15) — view + complete own + sign own campaigns
            'training.view',
            'training.complete',
            'attestations.sign',
            'certifications.view',

            // Incidents (M16)
            'incidents.view',
            'incidents.create',
            'incidents.update',
            'incidents.attach_evidence',
            'incidents.loss_register',

            // Returns (M10) — view only
            'returns.view',
            'returns.dashboard',
        ],

        'control_tester' => [
            'policies.view',

            'cycles.view',

            'risks.view',

            'controls.view',
            'controls.test',

            'issues.view',
            'issues.create',
            'issues.update',
            'issues.transition',

            // Training (M15) — view + complete own + sign own campaigns
            'training.view',
            'training.complete',
            'attestations.sign',
            'certifications.view',

            // Incidents (M16)
            'incidents.view',
            'incidents.create',
            'incidents.attach_evidence',

            // Returns (M10) — view only
            'returns.view',
            'returns.dashboard',
        ],

        'policy_owner' => [
            'policies.view',
            'policies.create',
            'policies.update', // PolicyPolicy restricts to own drafts only
            'policies.transition.submit_for_review',

            // Training (M15) — view + complete own + sign own campaigns
            'training.view',
            'training.complete',
            'attestations.sign',
            'certifications.view',

            // Incidents (M16)
            'incidents.view',
            'incidents.create',

            // Returns (M10) — view only
            'returns.view',
            'returns.dashboard',
        ],

        'auditor' => [
            'policies.view',

            'cycles.view',

            'risks.view',

            'controls.view',

            'issues.view',

            'audit.view',
            'admin.access',

            // Training (M15) — view only for auditor; attestations.manage for viewing records
            'training.view',
            'attestations.manage',
            'certifications.view',

            // Incidents (M16) — read-only
            'incidents.view',

            // Returns (M10) — view + dashboard only
            'returns.view',
            'returns.dashboard',
        ],
    ];

    public function run(): void
    {
        // Flush the permission cache so fresh seeds are picked up immediately.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions (idempotent).
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Create roles and assign permissions.
        foreach ($this->rolePermissions as $roleName => $permissionNames) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissionNames);
        }

        // Assign all permissions to super_admin role explicitly as well,
        // so that hasPermissionTo() calls also work when Gate::before is not active
        // (e.g. in tests that call ->can() directly without going through Gate).
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());
    }
}
