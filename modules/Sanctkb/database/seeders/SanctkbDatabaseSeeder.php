<?php

namespace Modules\Sanctkb\Database\Seeders;

use Illuminate\Database\Seeder;

class SanctkbDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SanctionsSeeder::class,
        ]);
    }
}
