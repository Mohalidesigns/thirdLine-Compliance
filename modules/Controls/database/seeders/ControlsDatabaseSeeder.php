<?php

declare(strict_types=1);

namespace Modules\Controls\Database\Seeders;

use Illuminate\Database\Seeder;

class ControlsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ControlsSeeder::class,
            ControlTestSeeder::class,
            IssueSeeder::class,
        ]);
    }
}
