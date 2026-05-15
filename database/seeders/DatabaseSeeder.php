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

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password'), 'is_admin' => true],
        );

        $this->call([
            LibraryDatabaseSeeder::class,
            SanctkbDatabaseSeeder::class,
            PolicySeeder::class,
            RcsaDatabaseSeeder::class,
            ControlsDatabaseSeeder::class,
        ]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY vw_penalty_exposure');
        }
    }
}

