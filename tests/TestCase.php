<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate as a freshly created user who has the given role.
     *
     * The role is created via Spatie's firstOrCreate so the test does not
     * depend on the seeder having run. Permissions are NOT auto-synced here;
     * if a test exercises a permission check it should run the
     * RolesAndPermissionsSeeder first (or call firstOrCreate for the permission
     * manually). For most RBAC tests, using actingAsRole after running the
     * seeder (via RefreshDatabase + the seeder call) is the correct pattern.
     */
    public function actingAsRole(string $role, array $attributes = []): static
    {
        Role::firstOrCreate(
            ['name' => $role, 'guard_name' => 'web']
        );

        $user = User::factory()->create($attributes);
        $user->assignRole($role);

        return $this->actingAs($user);
    }
}
