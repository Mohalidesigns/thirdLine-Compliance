<?php

declare(strict_types=1);

namespace Modules\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Training\Models\Certification;

class CertificationSeeder extends Seeder
{
    public function run(): void
    {
        // compliance@ — ACAMS expiring in 45 days.
        $compliance = User::where('email', 'compliance@example.com')->first();
        if ($compliance !== null) {
            Certification::firstOrCreate(
                ['user_id' => $compliance->id, 'name' => 'ACAMS'],
                [
                    'issuing_body' => 'Association of Certified Anti-Money Laundering Specialists',
                    'certificate_no' => 'ACAMS-2023-00142',
                    'issued_at' => now()->subYears(3)->toDateString(),
                    'expires_at' => now()->addDays(45)->toDateString(),
                    'status' => 'expiring',
                ],
            );
        }

        // auditor@ — CFE expired 30 days ago.
        $auditor = User::where('email', 'auditor@example.com')->first();
        if ($auditor !== null) {
            Certification::firstOrCreate(
                ['user_id' => $auditor->id, 'name' => 'CFE'],
                [
                    'issuing_body' => 'Association of Certified Fraud Examiners',
                    'certificate_no' => 'CFE-2021-77301',
                    'issued_at' => now()->subYears(4)->toDateString(),
                    'expires_at' => now()->subDays(30)->toDateString(),
                    'status' => 'expired',
                ],
            );
        }

        // tester@ — CIPP active.
        $tester = User::where('email', 'tester@example.com')->first();
        if ($tester !== null) {
            Certification::firstOrCreate(
                ['user_id' => $tester->id, 'name' => 'CIPP/A'],
                [
                    'issuing_body' => 'International Association of Privacy Professionals',
                    'certificate_no' => 'IAPP-CIPP-2024-00891',
                    'issued_at' => now()->subMonths(10)->toDateString(),
                    'expires_at' => now()->addMonths(14)->toDateString(),
                    'status' => 'active',
                ],
            );
        }
    }
}
