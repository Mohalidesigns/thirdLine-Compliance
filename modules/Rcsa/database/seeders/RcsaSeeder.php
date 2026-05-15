<?php

declare(strict_types=1);

namespace Modules\Rcsa\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Modules\Rcsa\Models\RiskWorkshopNote;

class RcsaSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('users')->value('id') ?? 1;

        $cycles = [
            [
                'reference' => 'BRA-2026-0001',
                'name' => 'Q1 2026 Retail Banking RCSA',
                'lob' => 'Retail',
                'state' => 'in_review',
                'cycle_year' => 2026,
                'cycle_quarter' => 1,
                'started_at' => '2026-01-06',
                'sla_due_date' => '2026-02-05',
                'methodology' => '3x3',
                'summary' => 'Q1 RCSA for Retail Banking covering AML, operational, cyber and conduct risk categories.',
                'lead_assessor_id' => $userId,
                'risks' => [
                    [
                        'title' => 'Inadequate CDD on high-risk PEP customers',
                        'description' => 'Enhanced due diligence procedures for Politically Exposed Persons are not being consistently applied at onboarding and during periodic reviews, creating significant AML/CFT exposure.',
                        'category' => 'aml',
                        'risk_owner' => 'CMLCO / Compliance',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 2,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'Enhanced KYC procedures implemented; automated PEP screening via World-Check integrated; quarterly CDD refresh for high-risk customers.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Aged debit exposure on unverified BVN accounts',
                        'description' => 'A cohort of retail accounts have unverified BVN records due to NIN-BVN linkage failures. These accounts carry aged debit balances exposing the bank to recovery risk.',
                        'category' => 'operational',
                        'risk_owner' => 'Operations / Retail Banking',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 2,
                        'residual_likelihood' => 2,
                        'residual_impact' => 2,
                        'mitigation_summary' => 'BVN reconciliation project underway; unverified accounts flagged and restricted from debit above NGN 50,000.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Failure to file goAML STR within 24h',
                        'description' => 'Systematic delay in STR filing to NFIU goAML portal due to manual review queues exceeding 24-hour window mandated under CBN AML/CFT Regulations 2022 Section 14(3).',
                        'category' => 'compliance',
                        'risk_owner' => 'CMLCO',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 3,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'Automated STR routing to NFIU portal in development; escalation SLA enforced. Interim: daily CMLCO review queue.',
                        'accept_basis' => 'Residual rating critical — accepted pending system automation go-live Q3 2026. Board Risk Committee informed.',
                    ],
                    [
                        'title' => 'Cybersecurity gap in mobile-banking app SDK',
                        'description' => 'Third-party mobile SDK used in the retail banking app has a known CVE (CVSS 8.1) that allows session token extraction on rooted Android devices. Patch not yet applied by vendor.',
                        'category' => 'cyber',
                        'risk_owner' => 'CISO / Digital Banking',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 2,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'Root-device detection added as compensating control; vendor on notice. SDK replacement project approved for Q2 2026.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Inadequate segregation of duties in teller operations',
                        'description' => 'Branch tellers can both initiate and approve cash transactions above NGN 500,000 threshold due to understaffing at three Abuja branches, creating fraud risk.',
                        'category' => 'operational',
                        'risk_owner' => 'Head of Retail Operations',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 2,
                        'residual_likelihood' => 1,
                        'residual_impact' => 2,
                        'mitigation_summary' => 'Dual authorisation enforced via CBS override; CCTV footage reviewed weekly; branch headcount increase approved.',
                        'accept_basis' => null,
                    ],
                ],
            ],
            [
                'reference' => 'BRA-2026-0002',
                'name' => 'Q2 2026 Treasury RCSA',
                'lob' => 'Treasury',
                'state' => 'scoring',
                'cycle_year' => 2026,
                'cycle_quarter' => 2,
                'started_at' => '2026-04-01',
                'sla_due_date' => '2026-05-01',
                'methodology' => '5x5',
                'summary' => 'Q2 RCSA for Treasury covering market, liquidity, and credit counterparty risks.',
                'lead_assessor_id' => $userId,
                'risks' => [
                    [
                        'title' => 'FX position limit breach risk under naira volatility',
                        'description' => 'Extended naira depreciation pressure increases probability of Treasury FX overnight position exceeding CBN-approved single-day limit of USD 10M net open position.',
                        'category' => 'market',
                        'risk_owner' => 'Head of Treasury',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 4,
                        'residual_likelihood' => 2,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'Intraday NOP monitoring dashboard deployed; automated alerts at 80% of limit; ALCO daily briefing.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'LCR breach risk under stressed deposit outflow scenario',
                        'description' => 'Stress test modelling shows a 15% retail deposit run-off within 30 days would breach the CBN LCR minimum of 100%, based on current HQLA composition.',
                        'category' => 'liquidity',
                        'risk_owner' => 'ALCO / Head of Treasury',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 5,
                        'residual_likelihood' => 1,
                        'residual_impact' => 4,
                        'mitigation_summary' => 'HQLA buffer increased by NGN 15B via FGN bonds acquisition; contingency funding plan activated for stress scenario.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Counterparty concentration in interbank placements',
                        'description' => 'Over 40% of Treasury interbank placements are placed with a single Tier-1 counterparty bank, creating single-name concentration risk beyond CBN counterparty limits.',
                        'category' => 'credit',
                        'risk_owner' => 'Credit Risk / Head of Treasury',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 4,
                        'residual_likelihood' => 1,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'Diversification plan approved by Board Credit Committee; maximum 25% single-counterparty limit enforced effective Q3 2026.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Model risk in internal VaR estimation',
                        'description' => 'In-house VaR model uses 2-year historical simulation that may underestimate tail risk under current high-volatility naira regime. Independent model validation overdue by 6 months.',
                        'category' => 'market',
                        'risk_owner' => 'Head of Market Risk',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 2,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'External model validation vendor engaged; stress VaR supplement added as conservative overlay.',
                        'accept_basis' => null,
                    ],
                ],
            ],
            [
                'reference' => 'BRA-2026-0003',
                'name' => 'Q3 2026 Compliance RCSA',
                'lob' => 'Compliance',
                'state' => 'data_capture',
                'cycle_year' => 2026,
                'cycle_quarter' => 3,
                'started_at' => '2026-07-01',
                'sla_due_date' => '2026-07-31',
                'methodology' => '3x3',
                'summary' => 'Q3 RCSA for the Compliance function covering regulatory breach, AML, and conduct risk.',
                'lead_assessor_id' => $userId,
                'risks' => [
                    [
                        'title' => 'Incomplete NDPA data protection impact assessments',
                        'description' => 'Multiple new digital products launched in H1 2026 lack completed Data Protection Impact Assessments (DPIAs) required under Nigeria Data Protection Act 2023 before go-live.',
                        'category' => 'compliance',
                        'risk_owner' => 'DPO / Digital Innovation',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 2,
                        'residual_likelihood' => 2,
                        'residual_impact' => 2,
                        'mitigation_summary' => 'DPIA checklist integrated into product launch gate process; DPO sign-off mandatory before production deployment.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Regulatory change management lag for CBN circulars',
                        'description' => 'Average time from CBN circular publication to internal policy update is 47 days against a target of 14 days, creating compliance gaps during transition periods.',
                        'category' => 'compliance',
                        'risk_owner' => 'Head of Compliance',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 2,
                        'residual_impact' => 2,
                        'mitigation_summary' => 'Automated CBN circular monitoring system deployed; 48h review SLA enforced with escalation matrix.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Sanctions screening false-negative risk on OFAC updates',
                        'description' => 'Delay of up to 4 hours between OFAC SDN list update and system integration creates a window where transactions may process against newly sanctioned entities.',
                        'category' => 'aml',
                        'risk_owner' => 'CMLCO / IT',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 1,
                        'residual_impact' => 3,
                        'mitigation_summary' => 'API integration with OFAC updated to real-time pull (15-minute refresh); manual override monitoring maintained.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Staff AML training completion rate below 95% threshold',
                        'description' => 'Annual mandatory AML training completion rate stands at 78% as of Q2 2026, below the 95% CBN-mandated threshold. Non-completers include customer-facing branch staff.',
                        'category' => 'aml',
                        'risk_owner' => 'Head of Compliance / HR',
                        'inherent_likelihood' => 3,
                        'inherent_impact' => 2,
                        'residual_likelihood' => 2,
                        'residual_impact' => 2,
                        'mitigation_summary' => 'Training deadline enforced with system access suspension after 30-day grace period; completion tracking dashboard live.',
                        'accept_basis' => null,
                    ],
                    [
                        'title' => 'Reputational risk from pending CBN enforcement action',
                        'description' => 'Bank received a preliminary inquiry letter from CBN Banking Supervision Department related to Q4 2025 consumer protection complaints. Public disclosure risk if escalated to formal enforcement.',
                        'category' => 'reputational',
                        'risk_owner' => 'MD/CEO / Head of Compliance',
                        'inherent_likelihood' => 2,
                        'inherent_impact' => 3,
                        'residual_likelihood' => 1,
                        'residual_impact' => 2,
                        'mitigation_summary' => 'Legal and external regulatory counsel engaged; complaint remediation programme initiated; CBN liaison officer appointed.',
                        'accept_basis' => null,
                    ],
                ],
            ],
        ];

        foreach ($cycles as $cycleData) {
            $risksData = $cycleData['risks'];
            unset($cycleData['risks']);
            $state = $cycleData['state'];
            unset($cycleData['state']);

            $cycle = RiskAssessmentCycle::create(array_merge($cycleData, ['tenant_id' => 1]));

            DB::table('risk_assessment_cycles')
                ->where('id', $cycle->id)
                ->update(['state' => $state]);

            foreach ($risksData as $index => $riskData) {
                $maxRiskId = Risk::withoutGlobalScopes()->max('id') ?? 0;
                Risk::create(array_merge($riskData, [
                    'reference' => 'RISK-'.str_pad((string) ($maxRiskId + 1), 4, '0', STR_PAD_LEFT),
                    'cycle_id' => $cycle->id,
                    'tenant_id' => 1,
                    'linked_obligation_ids' => [],
                ]));
            }

            RiskWorkshopNote::create([
                'cycle_id' => $cycle->id,
                'recorded_by' => $userId,
                'recorded_at' => now()->subDays(rand(5, 20)),
                'attendees' => ['Compliance Team', 'Risk Manager', 'LOB Head'],
                'notes' => "Initial risk identification workshop completed. All major risk categories reviewed with LOB representatives. Risk register draft circulated for validation.",
            ]);
        }
    }
}
