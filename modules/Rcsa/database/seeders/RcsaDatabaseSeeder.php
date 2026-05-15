<?php

declare(strict_types=1);

namespace Modules\Rcsa\Database\Seeders;

use Illuminate\Database\Seeder;

class RcsaDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RiskAppetiteThresholdSeeder::class,
            RcsaSeeder::class,
        ]);
    }
}
