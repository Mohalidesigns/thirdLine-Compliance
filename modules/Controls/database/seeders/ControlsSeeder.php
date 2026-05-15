<?php

declare(strict_types=1);

namespace Modules\Controls\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Controls\Models\Control;
use Modules\Controls\Services\SampleSizeCalculator;

class ControlsSeeder extends Seeder
{
    public function run(): void
    {
        $calculator = app(SampleSizeCalculator::class);

        $obligationIds = DB::table('obligations')->pluck('id')->take(10)->toArray();

        $controlsData = [
            [
                'title' => 'Daily reconciliation of suspense accounts',
                'description' => 'Daily automated reconciliation of all suspense/transit accounts to detect and resolve uncleared items within 24 hours. Alerts generated for items aged > 24 hours.',
                'control_type' => 'preventive',
                'nature' => 'automated',
                'frequency' => 'daily',
                'owner_team' => 'Operations / Finance',
                'status' => 'active',
                'days_ago' => 1,
            ],
            [
                'title' => 'Monthly BVN coverage report',
                'description' => 'Monthly detective report identifying accounts with unverified or mismatched BVN records. Accounts flagged for restriction if BVN verification incomplete for > 30 days.',
                'control_type' => 'detective',
                'nature' => 'manual',
                'frequency' => 'monthly',
                'owner_team' => 'Compliance / Operations',
                'status' => 'active',
                'days_ago' => 15,
            ],
            [
                'title' => 'Quarterly CDD refresh on high-risk customers',
                'description' => 'Periodic review and refresh of Customer Due Diligence documentation for all customers classified as high risk (PEPs, NGOs, cash-intensive businesses). Includes EDD for PEPs.',
                'control_type' => 'preventive',
                'nature' => 'manual',
                'frequency' => 'quarterly',
                'owner_team' => 'Compliance / Relationship Management',
                'status' => 'active',
                'days_ago' => 60,
            ],
            [
                'title' => 'Continuous AML rule monitoring',
                'description' => 'Real-time automated monitoring of transactions against AML typologies and thresholds in the AML transaction monitoring system. Alerts generated for suspicious patterns.',
                'control_type' => 'detective',
                'nature' => 'automated',
                'frequency' => 'continuous',
                'owner_team' => 'CMLCO / IT',
                'status' => 'active',
                'days_ago' => 0,
            ],
            [
                'title' => 'Annual penetration test',
                'description' => 'Comprehensive external penetration test by CBN-approved third-party vendor covering internet-facing infrastructure, internal network, mobile banking, and internet banking platforms.',
                'control_type' => 'detective',
                'nature' => 'manual',
                'frequency' => 'annual',
                'owner_team' => 'Information Security',
                'status' => 'active',
                'days_ago' => 200,
            ],
            [
                'title' => 'Sanctions list refresh validation',
                'description' => 'Automated validation that the sanctions screening system has successfully ingested the latest OFAC SDN, EU, UN, and OFAC consolidated lists within the expected refresh window.',
                'control_type' => 'preventive',
                'nature' => 'automated',
                'frequency' => 'daily',
                'owner_team' => 'CMLCO / IT',
                'status' => 'active',
                'days_ago' => 1,
            ],
            [
                'title' => 'Daily ATM cash counter reconciliation',
                'description' => 'Daily reconciliation of ATM cash loaded versus cash dispensed plus cash remaining, performed by branch operations manager with dual-sign-off requirement.',
                'control_type' => 'detective',
                'nature' => 'manual',
                'frequency' => 'daily',
                'owner_team' => 'Branch Operations',
                'status' => 'active',
                'days_ago' => 1,
            ],
            [
                'title' => 'Bank charges fairness review',
                'description' => 'Monthly review of customer charges applied against approved tariff schedule. Sample of 50 accounts reviewed for correct charge application and any over-charging reversed.',
                'control_type' => 'preventive',
                'nature' => 'manual',
                'frequency' => 'monthly',
                'owner_team' => 'Consumer Protection / Compliance',
                'status' => 'active',
                'days_ago' => 20,
            ],
            [
                'title' => 'Weekly fraud case review',
                'description' => 'Weekly review of all fraud incidents reported in the period by the fraud management team. Includes trend analysis, pattern detection, and escalation of systemic issues.',
                'control_type' => 'detective',
                'nature' => 'manual',
                'frequency' => 'weekly',
                'owner_team' => 'Fraud Risk Management',
                'status' => 'active',
                'days_ago' => 5,
            ],
            [
                'title' => 'goAML STR filing completeness check',
                'description' => 'Daily automated check that all STRs triggered in the AML system have been filed on NFIU goAML portal within the 24-hour regulatory window. Alert to CMLCO on breach.',
                'control_type' => 'detective',
                'nature' => 'automated',
                'frequency' => 'daily',
                'owner_team' => 'CMLCO',
                'status' => 'active',
                'days_ago' => 1,
            ],
            [
                'title' => 'Semi-annual NDPA data subject access request log review',
                'description' => 'Review of all Data Subject Access Requests received in the period to confirm timely response within statutory 30-day window and accurate data provided.',
                'control_type' => 'detective',
                'nature' => 'manual',
                'frequency' => 'semiannual',
                'owner_team' => 'DPO Office',
                'status' => 'active',
                'days_ago' => 150,
            ],
            [
                'title' => 'Internet banking transaction velocity monitoring',
                'description' => 'Continuous automated monitoring of internet banking transaction velocity per customer account. Automatic block triggered on velocity breach with alert to fraud team.',
                'control_type' => 'preventive',
                'nature' => 'automated',
                'frequency' => 'continuous',
                'owner_team' => 'Digital Banking / Fraud Risk',
                'status' => 'active',
                'days_ago' => 0,
            ],
            [
                'title' => 'Quarterly regulatory return accuracy check',
                'description' => 'Pre-submission review of CBN returns (FSS, CEX, FINA etc.) for arithmetic accuracy and consistency against source GL data before submission to regulators.',
                'control_type' => 'preventive',
                'nature' => 'hybrid',
                'frequency' => 'quarterly',
                'owner_team' => 'Finance / Regulatory Reporting',
                'status' => 'active',
                'days_ago' => 45,
            ],
            [
                'title' => 'Staff access rights quarterly review',
                'description' => 'Quarterly review of all user access rights in core banking and critical systems. Revocation of stale access, segregation of duties validation, and privileged access recertification.',
                'control_type' => 'preventive',
                'nature' => 'manual',
                'frequency' => 'quarterly',
                'owner_team' => 'Information Security / IT',
                'status' => 'active',
                'days_ago' => 80,
            ],
            [
                'title' => 'Event-driven incident escalation control',
                'description' => 'Post-incident review and escalation protocol triggered by any operational loss event > NGN 5M. Includes root cause analysis, remediation plan, and board notification within 48 hours.',
                'control_type' => 'corrective',
                'nature' => 'manual',
                'frequency' => 'event_driven',
                'owner_team' => 'Operational Risk / EXCO',
                'status' => 'draft',
                'days_ago' => null,
            ],
        ];

        foreach ($controlsData as $index => $data) {
            $daysAgo = $data['days_ago'];
            unset($data['days_ago']);

            $lastTestedAt = null;
            $nextTestDue = null;

            if ($daysAgo !== null && $data['status'] === 'active') {
                $lastTestedAt = now()->subDays($daysAgo)->subHours(rand(1, 8));
                $interval = $this->frequencyToDays($data['frequency']);
                $nextTestDue = Carbon::instance($lastTestedAt)->addDays($interval)->toDateString();
            }

            $linkedObligations = [];
            if (! empty($obligationIds)) {
                $sampleCount = min(3, count($obligationIds));
                $shuffled = $obligationIds;
                shuffle($shuffled);
                $linkedObligations = array_slice($shuffled, 0, $sampleCount);
            }

            $maxId = \Modules\Controls\Models\Control::withoutGlobalScopes()->max('id') ?? 0;
            Control::create(array_merge($data, [
                'reference' => 'CTL-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT),
                'tenant_id' => 1,
                'linked_obligation_ids' => $linkedObligations,
                'linked_risk_ids' => [],
                'last_tested_at' => $lastTestedAt,
                'next_test_due' => $nextTestDue,
            ]));
        }
    }

    private function frequencyToDays(string $frequency): int
    {
        return match ($frequency) {
            'continuous' => 1,
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'quarterly' => 90,
            'semiannual' => 180,
            'annual' => 365,
            default => 30,
        };
    }
}
