<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed roles and permissions so admin.access permission exists.
    (new RolesAndPermissionsSeeder)->run();
});

it('super_admin user can access the filament panel', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

it('auditor user can access the filament panel', function () {
    $auditor = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $auditor->assignRole('auditor');

    $this->actingAs($auditor)
        ->get('/admin')
        ->assertSuccessful();
});

it('compliance_officer user can access the filament panel', function () {
    $officer = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $officer->assignRole('compliance_officer');

    $this->actingAs($officer)
        ->get('/admin')
        ->assertSuccessful();
});

it('risk_owner user cannot access the filament panel', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user->assignRole('risk_owner');

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});

it('control_tester user cannot access the filament panel', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user->assignRole('control_tester');

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});

it('policy_owner user cannot access the filament panel', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $user->assignRole('policy_owner');

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});

it('unauthenticated user without any role cannot access the filament panel', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertStatus(403);
});
