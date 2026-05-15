<?php

declare(strict_types=1);

namespace Modules\Returns\Database\Seeders;

use Illuminate\Database\Seeder;

class ReturnsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReturnDefinitionsSeeder::class,
            ReturnRunSeeder::class,
        ]);
    }
}
