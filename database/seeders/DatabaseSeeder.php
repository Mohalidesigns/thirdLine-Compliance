<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Database\Seeders\ControlsDatabaseSeeder;
use Modules\Library\Database\Seeders\LibraryDatabaseSeeder;
use Modules\Policy\Database\Seeders\PolicySeeder;
use Modules\Rcsa\Database\Seeders\RcsaDatabaseSeeder;
use Modules\Sanctkb\Database\Seeders\SanctkbDatabaseSeeder;
use Modules\Training\Database\Seeders\TrainingDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // RBAC must seed first so that roles exist before module seeders run.
        $this->call(RolesAndPermissionsSeeder::class);

        // Primary admin — super_admin role.
        $admin = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Adaeze Okonkwo',
                'password' => bcrypt('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['super_admin']);

        // Demo users — one per role (realistic Nigerian-bank names).
        $demos = [
            [
                'name' => 'Chukwuemeka Nwosu',
                'email' => 'compliance@example.com',
                'role' => 'compliance_officer',
            ],
            [
                'name' => 'Ngozi Adeleke',
                'email' => 'riskowner@example.com',
                'role' => 'risk_owner',
            ],
            [
                'name' => 'Babatunde Fashola',
                'email' => 'policyowner@example.com',
                'role' => 'policy_owner',
            ],
            [
                'name' => 'Ifeoma Obi',
                'email' => 'tester@example.com',
                'role' => 'control_tester',
            ],
            [
                'name' => 'Emeka Eze',
                'email' => 'auditor@example.com',
                'role' => 'auditor',
            ],
        ];

        foreach ($demos as $demo) {
            $user = User::firstOrCreate(
                ['email' => $demo['email']],
                [
                    'name' => $demo['name'],
                    'password' => bcrypt('password'),
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles([$demo['role']]);
        }

        $this->call([
            LibraryDatabaseSeeder::class,
            SanctkbDatabaseSeeder::class,
            PolicySeeder::class,
            RcsaDatabaseSeeder::class,
            ControlsDatabaseSeeder::class,
            TrainingDatabaseSeeder::class,
        ]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY vw_penalty_exposure');
        }
    }
}
