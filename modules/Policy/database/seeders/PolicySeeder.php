<?php

declare(strict_types=1);

namespace Modules\Policy\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Policy\Models\Policy;
use Modules\Policy\Models\PolicyVersion;

class PolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'title' => 'AML/CFT Compliance Policy v3.2',
                'category' => 'aml',
                'owner_team' => 'Compliance',
                'state' => 'in_force',
                'effective_date' => '2024-01-01',
                'next_review_date' => '2025-01-01',
                'summary' => 'This policy establishes the framework for Anti-Money Laundering and Countering the Financing of Terrorism compliance across all business lines in accordance with CBN AML/CFT Regulations 2022 and NFIU guidelines.',
                'body' => "1. PURPOSE\nThis policy sets out the obligations of the Bank in relation to AML/CFT compliance.\n\n2. SCOPE\nApplies to all employees, subsidiaries, and third-party service providers.\n\n3. POLICY STATEMENT\nThe Bank is committed to full compliance with all applicable AML/CFT laws and regulations.",
                'version' => 3,
            ],
            [
                'title' => 'Customer Due Diligence Procedure',
                'category' => 'aml',
                'owner_team' => 'Compliance',
                'state' => 'in_force',
                'effective_date' => '2024-03-01',
                'next_review_date' => '2025-03-01',
                'summary' => 'Detailed procedure for conducting Customer Due Diligence (CDD) and Enhanced Due Diligence (EDD) in compliance with CBN KYC Manual 2023.',
                'body' => "1. INTRODUCTION\nThis procedure details the steps for CDD/EDD processes.\n\n2. CUSTOMER RISK CATEGORISATION\nCustomers are categorised as Low, Medium, or High risk.\n\n3. STANDARD CDD\nApplied to all customers at onboarding and periodically thereafter.",
                'version' => 2,
            ],
            [
                'title' => 'Whistleblowing & Speak-Up Policy',
                'category' => 'conduct',
                'owner_team' => 'Ethics Office',
                'state' => 'in_force',
                'effective_date' => '2023-07-01',
                'next_review_date' => '2025-07-01',
                'summary' => 'Policy governing protected disclosures by employees regarding suspected misconduct, in compliance with the Nigerian Whistleblower Protection Act.',
                'body' => "1. COMMITMENT\nThe Bank is committed to the highest standards of integrity and accountability.\n\n2. SCOPE\nAll employees may report concerns without fear of retaliation.\n\n3. REPORTING CHANNELS\nReports may be made to the Ethics Hotline, Ethics Officer, or directly to the Board.",
                'version' => 1,
            ],
            [
                'title' => 'Information Security Policy',
                'category' => 'cyber',
                'owner_team' => 'Information Security',
                'state' => 'in_force',
                'effective_date' => '2024-04-01',
                'next_review_date' => '2025-04-01',
                'summary' => 'Establishes the information security management framework aligned to ISO 27001:2022 and CBN Cybersecurity Framework.',
                'body' => "1. OBJECTIVES\nProtect confidentiality, integrity, and availability of information assets.\n\n2. SCOPE\nAll information systems, data, and personnel.\n\n3. RESPONSIBILITIES\nAll employees are responsible for complying with this policy.",
                'version' => 2,
            ],
            [
                'title' => 'Data Protection & Privacy Policy',
                'category' => 'data_protection',
                'owner_team' => 'DPO Office',
                'state' => 'in_review',
                'effective_date' => null,
                'next_review_date' => null,
                'summary' => 'Governs the processing of personal data in compliance with the Nigeria Data Protection Act 2023 (NDPA) and NDPC regulations.',
                'body' => "1. INTRODUCTION\nThis policy describes how we collect, use, and protect personal data.\n\n2. LEGAL BASIS\nProcessing is based on consent, contract performance, legal obligation, or legitimate interests.\n\n3. DATA SUBJECT RIGHTS\nIndividuals have rights to access, correct, delete, and port their data.",
                'version' => 1,
            ],
            [
                'title' => 'Vendor Risk Management Policy',
                'category' => 'risk',
                'owner_team' => 'Procurement',
                'state' => 'approved',
                'effective_date' => '2026-06-01',
                'next_review_date' => '2027-06-01',
                'summary' => 'Framework for identifying, assessing, and managing risks arising from third-party vendor relationships.',
                'body' => "1. PURPOSE\nEstablish a structured approach to vendor risk management.\n\n2. VENDOR CLASSIFICATION\nVendors are classified by criticality: Critical, Important, and Standard.\n\n3. DUE DILIGENCE\nAll vendors undergo risk-based due diligence before engagement.",
                'version' => 1,
            ],
            [
                'title' => 'Code of Business Conduct',
                'category' => 'conduct',
                'owner_team' => 'Human Resources',
                'state' => 'under_review',
                'effective_date' => '2023-01-01',
                'next_review_date' => '2026-01-01',
                'summary' => 'Sets out the standards of ethical conduct and business integrity expected of all employees and directors.',
                'body' => "1. OUR VALUES\nIntegrity, Respect, Excellence, Accountability.\n\n2. CONFLICTS OF INTEREST\nEmployees must avoid situations where personal interests conflict with Bank interests.\n\n3. GIFTS AND HOSPITALITY\nLimits and approval processes for gifts and hospitality.",
                'version' => 2,
            ],
            [
                'title' => 'Cybersecurity Incident Response Plan',
                'category' => 'cyber',
                'owner_team' => 'Information Security',
                'state' => 'draft',
                'effective_date' => null,
                'next_review_date' => null,
                'summary' => 'Defines the procedures for detecting, responding to, and recovering from cybersecurity incidents in accordance with CBN Cybersecurity Framework 2023.',
                'body' => "1. SCOPE\nApplies to all cyber incidents affecting bank systems.\n\n2. INCIDENT CLASSIFICATION\nSeverity levels: Critical, High, Medium, Low.\n\n3. RESPONSE PHASES\nDetection, Containment, Eradication, Recovery, Lessons Learned.",
                'version' => 1,
            ],
        ];

        foreach ($policies as $index => $data) {
            $state = $data['state'];
            $version = $data['version'] ?? 1;
            unset($data['state'], $data['version']);

            $policy = Policy::create(array_merge($data, [
                'reference' => 'POL-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
            ]));

            $stateMap = [
                'draft' => 'draft',
                'in_review' => 'in_review',
                'approved' => 'approved',
                'published' => 'published',
                'in_force' => 'in_force',
                'under_review' => 'under_review',
                'superseded' => 'superseded',
            ];

            // Write version and state directly — these fields are excluded from
            // $fillable to prevent mass-assignment over-exposure.
            $directUpdates = ['version' => $version];
            if ($state !== 'draft') {
                $directUpdates['state'] = $state;
            }
            DB::table('policies')->where('id', $policy->id)->update($directUpdates);

            if ($state !== 'draft') {
                PolicyVersion::create([
                    'policy_id' => $policy->id,
                    'version' => $version,
                    'state' => $state,
                    'body_snapshot' => $data['body'] ?? null,
                    'transitioned_by' => null,
                    'transitioned_at' => now(),
                    'transition_note' => 'Seeded initial state',
                ]);
            }
        }
    }
}
