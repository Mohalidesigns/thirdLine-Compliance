<?php

declare(strict_types=1);

namespace Modules\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Training\Models\AttestationCampaign;

class AttestationCampaignSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'test@example.com')->first();
        $createdBy = $admin?->id;

        $allRoles = ['super_admin', 'compliance_officer', 'risk_owner', 'control_tester', 'policy_owner', 'auditor'];

        $campaigns = [
            [
                'code' => 'ATT-COC-2026',
                'title' => 'Annual Code of Conduct 2026',
                'body' => '<h2>Annual Code of Conduct Attestation</h2><p>By signing this attestation, I confirm that I have read, understood, and agree to comply with the Bank\'s Code of Conduct and Ethics Policy. I acknowledge that any violation may result in disciplinary action up to and including termination of employment.</p><p>Key areas covered: conflicts of interest, gifts and entertainment, fair dealing, insider trading prevention, confidentiality, market conduct, whistleblower protections, and social media policy.</p>',
                'starts_at' => now()->startOfYear(),
                'ends_at' => now()->startOfYear()->addMonths(2),
                'mandatory_for_roles' => $allRoles,
                'status' => 'closed',
            ],
            [
                'code' => 'ATT-COI-2026',
                'title' => 'Conflict of Interest Disclosure 2026',
                'body' => '<h2>Conflict of Interest Disclosure</h2><p>I confirm that I have reviewed the Bank\'s Conflict of Interest Policy and declare the following: (a) I do not have any personal or financial interest that could impair my ability to act in the Bank\'s best interest; OR (b) I have disclosed any such interests to my line manager and the Compliance team.</p><p>I understand that failure to disclose a conflict of interest is itself a breach of policy.</p>',
                'starts_at' => now()->subMonths(1),
                'ends_at' => now()->addMonths(2),
                'mandatory_for_roles' => $allRoles,
                'status' => 'active',
            ],
            [
                'code' => 'ATT-NDPA-2026',
                'title' => 'Data Protection Acknowledgement 2026',
                'body' => '<h2>Nigeria Data Protection Act 2023 — Staff Acknowledgement</h2><p>I confirm that I have received training on the Nigeria Data Protection Act 2023 and the Bank\'s Data Protection Policy. I understand my obligations with respect to: (a) lawful basis for processing personal data; (b) data minimisation and purpose limitation; (c) data subject rights requests; (d) breach notification obligations; (e) third-party data sharing restrictions.</p>',
                'starts_at' => now()->subWeeks(2),
                'ends_at' => now()->addMonths(3),
                'mandatory_for_roles' => $allRoles,
                'status' => 'active',
            ],
        ];

        foreach ($campaigns as $data) {
            AttestationCampaign::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['created_by' => $createdBy]),
            );
        }
    }
}
