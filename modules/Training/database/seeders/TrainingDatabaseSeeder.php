<?php

declare(strict_types=1);

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;

class TrainingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TrainingCatalogSeeder::class,
            AttestationCampaignSeeder::class,
            EnrollmentSeeder::class,
            CertificationSeeder::class,
        ]);
    }
}
