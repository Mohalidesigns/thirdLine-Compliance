<?php

declare(strict_types=1);

namespace Modules\Rcsa\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiskAppetiteThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            ['lob' => 'Retail', 'category' => 'aml', 'acceptable_rating' => 'medium', 'breach_action' => 'Escalate to CMLCO and BRC; mitigation plan required within 10 days'],
            ['lob' => 'Retail', 'category' => 'operational', 'acceptable_rating' => 'medium', 'breach_action' => 'Operations risk committee review required'],
            ['lob' => 'Retail', 'category' => 'credit', 'acceptable_rating' => 'high', 'breach_action' => 'Board Credit Committee notification'],
            ['lob' => 'Retail', 'category' => 'compliance', 'acceptable_rating' => 'low', 'breach_action' => 'Immediate escalation to Head of Compliance and BRC'],
            ['lob' => 'Retail', 'category' => 'cyber', 'acceptable_rating' => 'medium', 'breach_action' => 'CISO-led remediation plan within 30 days'],
            ['lob' => 'Retail', 'category' => 'reputational', 'acceptable_rating' => 'low', 'breach_action' => 'MD/CEO and Communications team to be notified immediately'],
            ['lob' => 'Retail', 'category' => 'conduct', 'acceptable_rating' => 'low', 'breach_action' => 'Ethics committee and HR review required'],
            ['lob' => 'Retail', 'category' => 'strategic', 'acceptable_rating' => 'medium', 'breach_action' => 'Board Strategy committee review'],
            ['lob' => 'Retail', 'category' => 'liquidity', 'acceptable_rating' => 'medium', 'breach_action' => 'ALCO notification and contingency funding plan activation'],
            ['lob' => 'Retail', 'category' => 'market', 'acceptable_rating' => 'medium', 'breach_action' => 'Treasury risk committee and ALCO notification'],

            ['lob' => 'Corporate', 'category' => 'credit', 'acceptable_rating' => 'high', 'breach_action' => 'Board Credit Committee and CBN notification if single-obligor limit breached'],
            ['lob' => 'Corporate', 'category' => 'aml', 'acceptable_rating' => 'medium', 'breach_action' => 'Enhanced KYC review and goAML filing if STR threshold met'],
            ['lob' => 'Corporate', 'category' => 'operational', 'acceptable_rating' => 'medium', 'breach_action' => 'Operations risk committee review'],
            ['lob' => 'Corporate', 'category' => 'compliance', 'acceptable_rating' => 'low', 'breach_action' => 'Head of Compliance escalation and remediation plan'],
            ['lob' => 'Corporate', 'category' => 'reputational', 'acceptable_rating' => 'low', 'breach_action' => 'MD/CEO briefing within 24 hours'],

            ['lob' => 'Treasury', 'category' => 'market', 'acceptable_rating' => 'medium', 'breach_action' => 'ALCO review and VaR limit re-assessment'],
            ['lob' => 'Treasury', 'category' => 'liquidity', 'acceptable_rating' => 'low', 'breach_action' => 'Immediate ALCO escalation and CBN notification if LCR breached'],
            ['lob' => 'Treasury', 'category' => 'operational', 'acceptable_rating' => 'medium', 'breach_action' => 'Treasury operations review and control strengthening'],
            ['lob' => 'Treasury', 'category' => 'compliance', 'acceptable_rating' => 'low', 'breach_action' => 'Head of Compliance and CFO notification'],
            ['lob' => 'Treasury', 'category' => 'credit', 'acceptable_rating' => 'medium', 'breach_action' => 'Counterparty credit limit review by Credit Risk'],

            ['lob' => 'Operations', 'category' => 'operational', 'acceptable_rating' => 'medium', 'breach_action' => 'COO-led root cause analysis and remediation within 30 days'],
            ['lob' => 'Operations', 'category' => 'cyber', 'acceptable_rating' => 'medium', 'breach_action' => 'CISO-led investigation and patching plan within 15 days'],
            ['lob' => 'Operations', 'category' => 'aml', 'acceptable_rating' => 'medium', 'breach_action' => 'CMLCO and operations head joint review'],
            ['lob' => 'Operations', 'category' => 'compliance', 'acceptable_rating' => 'medium', 'breach_action' => 'Compliance team corrective action plan'],

            ['lob' => 'IT', 'category' => 'cyber', 'acceptable_rating' => 'low', 'breach_action' => 'CISO and MD/CEO briefing; external penetration test within 60 days'],
            ['lob' => 'IT', 'category' => 'operational', 'acceptable_rating' => 'medium', 'breach_action' => 'IT risk committee review and remediation plan'],
            ['lob' => 'IT', 'category' => 'strategic', 'acceptable_rating' => 'medium', 'breach_action' => 'CTO and board technology committee review'],
            ['lob' => 'IT', 'category' => 'compliance', 'acceptable_rating' => 'medium', 'breach_action' => 'Data protection officer and CISO joint review'],

            ['lob' => 'Compliance', 'category' => 'compliance', 'acceptable_rating' => 'low', 'breach_action' => 'EXCO escalation and regulatory notification where required'],
            ['lob' => 'Compliance', 'category' => 'aml', 'acceptable_rating' => 'low', 'breach_action' => 'CMLCO immediate action; NFIU/goAML reporting obligation assessed'],
            ['lob' => 'Compliance', 'category' => 'reputational', 'acceptable_rating' => 'low', 'breach_action' => 'EXCO and Board notification within 24 hours'],
        ];

        foreach ($thresholds as $threshold) {
            DB::table('risk_appetite_thresholds')->updateOrInsert(
                ['tenant_id' => 1, 'lob' => $threshold['lob'], 'category' => $threshold['category']],
                array_merge($threshold, ['tenant_id' => 1, 'updated_at' => now()]),
            );
        }
    }
}
