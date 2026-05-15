<?php

declare(strict_types=1);

namespace Modules\Controls\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Controls\Models\Issue;

class IssueSeeder extends Seeder
{
    public function run(): void
    {
        $manualIssues = [
            [
                'source_type' => 'manual',
                'title' => 'CBN circular on cybersecurity framework compliance gaps identified',
                'description' => 'Internal gap assessment against CBN Cybersecurity Framework 2.0 (2023) identified 7 controls not fully implemented. Reported by Head of Information Security during management committee review.',
                'severity' => 'high',
                'status' => 'in_progress',
                'owner_team' => 'Information Security',
                'due_date' => now()->addDays(45)->toDateString(),
            ],
            [
                'source_type' => 'manual',
                'title' => 'Unresolved customer complaint backlog exceeds SLA threshold',
                'description' => 'Customer complaints resolution rate has fallen to 72% within 7-day SLA target as reported by Consumer Protection Unit. CBN Consumer Protection Framework requires 85% resolution within 7 days.',
                'severity' => 'medium',
                'status' => 'open',
                'owner_team' => 'Consumer Protection / Customer Service',
                'due_date' => now()->addDays(14)->toDateString(),
            ],
            [
                'source_type' => 'manual',
                'title' => 'NDPA Article 24 data localisation non-compliance identified',
                'description' => 'DPO review found that two third-party cloud vendors process Nigerian resident data outside Nigeria without NDPC approval. Potential NDPA Section 44 violation.',
                'severity' => 'high',
                'status' => 'open',
                'owner_team' => 'DPO Office / Legal',
                'due_date' => now()->addDays(21)->toDateString(),
            ],
            [
                'source_type' => 'manual',
                'title' => 'Missing FATCA GIIN registration for new subsidiary',
                'description' => 'The bank\'s newly licensed microfinance subsidiary has not been registered under FATCA GIIN programme, creating IRS reporting obligation breach risk for US account holders.',
                'severity' => 'medium',
                'status' => 'open',
                'owner_team' => 'Tax / Compliance',
                'due_date' => now()->addDays(30)->toDateString(),
            ],
            [
                'source_type' => 'audit_finding',
                'title' => 'Internal audit finding: documented evidence gaps in CBN returns review process',
                'description' => 'Internal audit Q1 2026 found that the pre-submission review of CBN FSS returns lacked documented sign-off evidence for 3 of 6 months reviewed. Risk of regulatory challenge on submission accuracy.',
                'severity' => 'medium',
                'status' => 'open',
                'owner_team' => 'Finance / Regulatory Reporting',
                'due_date' => now()->addDays(60)->toDateString(),
            ],
        ];

        foreach ($manualIssues as $issueData) {
            $maxId = Issue::withoutGlobalScopes()->max('id') ?? 0;
            Issue::create(array_merge($issueData, [
                'tenant_id' => 1,
                'reference' => 'ISS-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT),
            ]));
        }
    }
}
