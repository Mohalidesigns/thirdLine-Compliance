<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * One-off migration: grant super_admin role to every user with is_admin = true.
 *
 * This bridges the old binary is_admin flag to the new RBAC model.
 * The is_admin column is kept in place; canAccessPanel() now uses admin.access
 * permission instead. This migration must run after the Spatie permission tables
 * exist (which they do — the 2026_05_14_183952 migration created them).
 *
 * Reversible: down() removes the super_admin role from those users, but does
 * NOT restore the is_admin behaviour (which has been disabled in User::canAccessPanel).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only act if both tables exist — safe to run in a fresh environment
        // where roles haven't been seeded yet.
        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('users')) {
            return;
        }

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        User::where('is_admin', true)->each(function (User $user) use ($superAdminRole) {
            if (! $user->hasRole('super_admin')) {
                $user->assignRole($superAdminRole);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $superAdminRole = Role::where('name', 'super_admin')->first();

        if ($superAdminRole === null) {
            return;
        }

        User::where('is_admin', true)->each(function (User $user) use ($superAdminRole) {
            $user->removeRole($superAdminRole);
        });
    }
};
