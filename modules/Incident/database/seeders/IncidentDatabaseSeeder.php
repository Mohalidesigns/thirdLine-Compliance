<?php

declare(strict_types=1);

namespace Modules\Incident\Database\Seeders;

use Illuminate\Database\Seeder;

class IncidentDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IncidentCatalogSeeder::class,
        ]);
    }
}
