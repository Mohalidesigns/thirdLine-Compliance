<?php

declare(strict_types=1);

namespace Modules\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Training\Models\Training;

class TrainingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'test@example.com')->first();
        $createdBy = $admin?->id;

        $trainings = [
            [
                'code' => 'TRN-AML-001',
                'title' => 'AML/CFT Refresher 2026',
                'description' => 'Annual refresher on Anti-Money Laundering and Counter-Financing of Terrorism obligations under NFIU guidelines, FATF recommendations, and CBN AML/CFT regulations.',
                'category' => 'aml',
                'is_mandatory' => true,
                'target_roles' => [],
                'sla_days' => 30,
            ],
            [
                'code' => 'TRN-SAN-001',
                'title' => 'Sanctions Screening Workflow',
                'description' => 'Training on the bank\'s sanctions screening process, covering OFAC, UN, EU, and CBN sanctions lists, escalation procedures, and goAML reporting.',
                'category' => 'sanctions',
                'is_mandatory' => true,
                'target_roles' => [],
                'sla_days' => 30,
            ],
            [
                'code' => 'TRN-NDPA-001',
                'title' => 'NDPA Privacy 101',
                'description' => 'Introduction to the Nigeria Data Protection Act 2023 — data subject rights, consent requirements, breach notification obligations, and the role of the DPO.',
                'category' => 'ndpa',
                'is_mandatory' => true,
                'target_roles' => [],
                'sla_days' => 45,
            ],
            [
                'code' => 'TRN-ABAC-001',
                'title' => 'ABAC and Gifts & Entertainment Policy',
                'description' => 'Training on the Anti-Bribery and Anti-Corruption framework, gifts and entertainment policy thresholds, facilitation payments prohibition, and reporting obligations.',
                'category' => 'abac',
                'is_mandatory' => true,
                'target_roles' => [],
                'sla_days' => 30,
            ],
            [
                'code' => 'TRN-CON-001',
                'title' => 'Conduct & Ethics Annual',
                'description' => 'Annual code of conduct and ethics training covering conflicts of interest, insider trading, whistleblower channels, market abuse, and the bank\'s values.',
                'category' => 'conduct',
                'is_mandatory' => true,
                'target_roles' => [],
                'sla_days' => 30,
            ],
            [
                'code' => 'TRN-CYB-001',
                'title' => 'Cyber Awareness',
                'description' => 'Phishing awareness, password hygiene, social engineering defence, mobile device security, and incident reporting procedures for all bank staff.',
                'category' => 'cyber',
                'is_mandatory' => true,
                'target_roles' => [],
                'sla_days' => 30,
            ],
            [
                'code' => 'TRN-CUS-001',
                'title' => 'Customer Protection Standards',
                'description' => 'CBN Consumer Protection Regulations — fair treatment, transparent pricing, complaint handling SLAs, and the bank\'s customer protection commitments.',
                'category' => 'customer_protection',
                'is_mandatory' => false,
                'target_roles' => ['compliance_officer', 'control_tester'],
                'sla_days' => 60,
            ],
            [
                'code' => 'TRN-RISK-001',
                'title' => 'Operational Risk Basics',
                'description' => 'Introduction to operational risk management — the risk taxonomy, RCSA methodology, key risk indicators, control self-assessment, and loss event reporting.',
                'category' => 'general',
                'is_mandatory' => false,
                'target_roles' => ['risk_owner', 'compliance_officer'],
                'sla_days' => 60,
            ],
            [
                'code' => 'TRN-ETH-001',
                'title' => 'Insider Trading Prevention',
                'description' => 'Securities and Exchange Commission rules on insider trading — material non-public information, blackout periods, personal account dealing restrictions.',
                'category' => 'ethics',
                'is_mandatory' => false,
                'target_roles' => ['compliance_officer', 'auditor'],
                'sla_days' => 45,
            ],
            [
                'code' => 'TRN-WB-001',
                'title' => 'Whistleblower Channels & Speak-Up Culture',
                'description' => 'How to use the bank\'s whistleblower hotline, anonymity protections, the role of the Ethics Officer, and non-retaliation policy.',
                'category' => 'conduct',
                'is_mandatory' => false,
                'target_roles' => [],
                'sla_days' => 60,
            ],
        ];

        foreach ($trainings as $data) {
            Training::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'source' => 'native',
                    'created_by' => $createdBy,
                ]),
            );
        }
    }
}
