<?php

declare(strict_types=1);

namespace Modules\Incident\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Incident\Models\IncidentAction;
use Modules\Incident\Models\OperationalLossEvent;
use Modules\Incident\Services\IncidentService;

class IncidentCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(IncidentService::class);

        // Grab demo users — created in DatabaseSeeder, so they exist.
        $compliance = User::where('email', 'compliance@example.com')->first();
        $riskOwner = User::where('email', 'riskowner@example.com')->first();
        $admin = User::where('email', 'test@example.com')->first();
        $tester = User::where('email', 'tester@example.com')->first();

        $reporter = $compliance ?? $admin;
        $assignee = $riskOwner ?? $admin;

        // 1 — Phishing campaign targeting retail customers (cyber, high, remediation)
        $phishing = $service->create([
            'title' => 'Phishing campaign targeting retail customers',
            'description' => 'A large-scale phishing campaign was detected targeting retail banking customers via SMS impersonating FirstBank. Approximately 500 customers were potentially exposed.',
            'category' => 'cyber',
            'severity' => 'high',
            'basel_category' => 'external_fraud',
            'occurred_at' => now()->subDays(15),
            'detected_at' => now()->subDays(12),
            'is_cyber_incident' => true,
            'is_data_breach' => false,
            'affects_customers' => true,
            'affected_customer_count' => 500,
            'assigned_to' => $assignee?->id,
        ], $reporter?->id ?? 1);

        // Transition phishing to remediation
        $service->transition($phishing, 'triaged', $reporter?->id ?? 1);
        $service->transition($phishing, 'investigating', $reporter?->id ?? 1);
        $service->transition($phishing, 'remediation', $reporter?->id ?? 1);

        // Add 2 actions
        $phishingAction1 = IncidentAction::create([
            'tenant_id' => 1,
            'incident_id' => $phishing->id,
            'type' => 'corrective',
            'title' => 'Block phishing domains at firewall',
            'description' => 'Identify and block all identified phishing domains via NGFW policy update.',
            'owner_user_id' => $assignee?->id,
            'due_at' => now()->addDays(2)->toDateString(),
        ]);

        $phishingAction2 = IncidentAction::create([
            'tenant_id' => 1,
            'incident_id' => $phishing->id,
            'type' => 'preventive',
            'title' => 'Customer awareness SMS blast',
            'description' => 'Send security awareness SMS to all retail customers warning about phishing.',
            'owner_user_id' => $compliance?->id,
            'due_at' => now()->addDays(5)->toDateString(),
        ]);

        // Add 1 evidence
        $service->attachEvidence($phishing, [
            'type' => 'screenshot',
            'title' => 'Phishing SMS screenshot from customer report',
            'description' => 'Screenshot of the SMS phishing attempt shared by a customer via the helpdesk.',
        ], $tester?->id ?? 1);

        // 2 — POS terminal data theft at Lagos branch (data_breach, critical, closed)
        $pos = $service->create([
            'title' => 'POS terminal data theft at Lagos branch',
            'description' => 'Skimming device found on 3 POS terminals at Victoria Island branch. Customer card data potentially compromised.',
            'category' => 'data_breach',
            'severity' => 'critical',
            'basel_category' => 'external_fraud',
            'occurred_at' => now()->subDays(30),
            'detected_at' => now()->subDays(28),
            'is_cyber_incident' => false,
            'is_data_breach' => true,
            'affects_customers' => true,
            'affected_customer_count' => 120,
            'financial_impact' => 4500000.00,
            'currency' => 'NGN',
            'root_cause' => 'Inadequate physical inspection procedures for POS terminals at branch.',
            'lessons_learned' => 'Monthly physical inspection protocol for all POS devices must be implemented.',
            'assigned_to' => $assignee?->id,
        ], $reporter?->id ?? 1);

        // Transition POS to closed — requires evidence first
        $service->transition($pos, 'triaged', $reporter?->id ?? 1);
        $service->transition($pos, 'investigating', $reporter?->id ?? 1);
        $service->transition($pos, 'remediation', $reporter?->id ?? 1);
        $service->transition($pos, 'resolved', $reporter?->id ?? 1);

        // Attach evidence before closing
        $service->attachEvidence($pos, [
            'type' => 'document',
            'title' => 'Forensic report — POS skimmer investigation',
            'description' => 'External forensic investigation report confirming scope of card data compromise.',
        ], $reporter?->id ?? 1);

        $service->transition($pos, 'closed', $reporter?->id ?? 1);

        // Mark NDPC notification as submitted
        $posNdpc = $pos->notifications()->where('regulator', 'ndpc')->first();
        if ($posNdpc) {
            $posNdpc->update([
                'status' => 'submitted',
                'notified_at' => now()->subDays(25),
                'notification_reference' => 'NDPC-2026-0412-SK',
            ]);
        }

        // Loss event
        OperationalLossEvent::create([
            'tenant_id' => 1,
            'incident_id' => $pos->id,
            'basel_category' => 'external_fraud',
            'gross_loss' => 4500000.00,
            'recovery_amount' => 1200000.00,
            'net_loss_currency' => 'NGN',
            'event_date' => now()->subDays(30)->toDateString(),
            'recognized_date' => now()->subDays(25)->toDateString(),
        ]);

        // 3 — Suspected internal fraud — wire transfer rerouting (financial_crime, critical, investigating)
        $fraud = $service->create([
            'title' => 'Suspected internal fraud — wire transfer rerouting',
            'description' => 'MLRO flagged unusual pattern of small-value wire transfers totalling N15m to non-customer accounts. Suspected collusion by operations staff.',
            'category' => 'financial_crime',
            'severity' => 'critical',
            'basel_category' => 'internal_fraud',
            'occurred_at' => now()->subDays(8),
            'detected_at' => now()->subDays(6),
            'reporter_external_source' => 'MLRO_email',
            'is_cyber_incident' => false,
            'is_data_breach' => false,
            'affects_customers' => false,
            'financial_impact' => 15000000.00,
            'currency' => 'NGN',
            'assigned_to' => $compliance?->id,
        ], $reporter?->id ?? 1);

        $service->transition($fraud, 'triaged', $reporter?->id ?? 1);
        $service->transition($fraud, 'investigating', $reporter?->id ?? 1);

        IncidentAction::create([
            'tenant_id' => 1,
            'incident_id' => $fraud->id,
            'type' => 'corrective',
            'title' => 'Freeze suspected staff accounts',
            'description' => 'Work with HR and Legal to freeze system access for identified suspects pending investigation.',
            'owner_user_id' => $compliance?->id,
            'due_at' => now()->addDay()->toDateString(),
        ]);

        OperationalLossEvent::create([
            'tenant_id' => 1,
            'incident_id' => $fraud->id,
            'basel_category' => 'internal_fraud',
            'gross_loss' => 15000000.00,
            'recovery_amount' => 0,
            'net_loss_currency' => 'NGN',
            'event_date' => now()->subDays(8)->toDateString(),
            'recognized_date' => now()->subDays(6)->toDateString(),
        ]);

        // 4 — Customer complaint backlog SLA breach (customer_protection, medium, resolved)
        $sla = $service->create([
            'title' => 'Customer complaint backlog SLA breach',
            'description' => 'Customer Protection team identified 340 overdue complaints exceeding 72-hour SLA in Q1 2026.',
            'category' => 'customer_protection',
            'severity' => 'medium',
            'occurred_at' => now()->subDays(45),
            'detected_at' => now()->subDays(20),
            'is_cyber_incident' => false,
            'is_data_breach' => false,
            'affects_customers' => true,
            'affected_customer_count' => 340,
            'root_cause' => 'Insufficient staffing in the customer resolution team during peak period.',
            'assigned_to' => $assignee?->id,
        ], $reporter?->id ?? 1);

        $service->transition($sla, 'triaged', $reporter?->id ?? 1);
        $service->transition($sla, 'investigating', $reporter?->id ?? 1);
        $service->transition($sla, 'remediation', $reporter?->id ?? 1);
        $service->transition($sla, 'resolved', $reporter?->id ?? 1);

        $service->attachEvidence($sla, [
            'type' => 'document',
            'title' => 'Complaint resolution report — March 2026',
            'description' => 'Report confirming all 340 complaints were resolved and customers notified.',
        ], $reporter?->id ?? 1);

        // 5 — DDoS attack on banking portal (cyber, high, triaged)
        $ddos = $service->create([
            'title' => 'DDoS attack on internet banking portal',
            'description' => 'Sustained DDoS attack targeting the retail internet banking portal caused degraded service for approximately 3 hours.',
            'category' => 'cyber',
            'severity' => 'high',
            'occurred_at' => now()->subDays(3),
            'detected_at' => now()->subDays(3),
            'is_cyber_incident' => true,
            'is_data_breach' => false,
            'affects_customers' => true,
            'affected_customer_count' => 0,
            'assigned_to' => $tester?->id,
        ], $reporter?->id ?? 1);

        $service->transition($ddos, 'triaged', $reporter?->id ?? 1);

        // 6 — Core banking outage 4h (operational, high, resolved, business_disruption)
        $outage = $service->create([
            'title' => 'Core banking system outage — 4 hours',
            'description' => 'Core banking system (Temenos T24) experienced unplanned outage from 14:00–18:00 on a weekday, preventing all teller and ATM transactions.',
            'category' => 'operational',
            'severity' => 'high',
            'basel_category' => 'business_disruption',
            'occurred_at' => now()->subDays(10),
            'detected_at' => now()->subDays(10),
            'is_cyber_incident' => false,
            'is_data_breach' => false,
            'affects_customers' => true,
            'affected_customer_count' => 0,
            'root_cause' => 'Database storage failure on primary node due to unpatched firmware bug.',
            'assigned_to' => $assignee?->id,
        ], $reporter?->id ?? 1);

        $service->transition($outage, 'triaged', $reporter?->id ?? 1);
        $service->transition($outage, 'investigating', $reporter?->id ?? 1);
        $service->transition($outage, 'remediation', $reporter?->id ?? 1);
        $service->transition($outage, 'resolved', $reporter?->id ?? 1);

        $service->attachEvidence($outage, [
            'type' => 'log',
            'title' => 'T24 system event log — outage period',
            'description' => 'Core banking event log extract covering the outage window.',
        ], $tester?->id ?? 1);

        OperationalLossEvent::create([
            'tenant_id' => 1,
            'incident_id' => $outage->id,
            'basel_category' => 'business_disruption',
            'gross_loss' => 2200000.00,
            'recovery_amount' => 0,
            'net_loss_currency' => 'NGN',
            'event_date' => now()->subDays(10)->toDateString(),
            'recognized_date' => now()->subDays(9)->toDateString(),
        ]);

        // 7 — Sanctions screening missed PEP (financial_crime, medium, investigating)
        $pep = $service->create([
            'title' => 'Sanctions screening missed PEP onboarding',
            'description' => 'Customer onboarded as SME was subsequently identified as a Politically Exposed Person (PEP) not flagged by the screening tool.',
            'category' => 'financial_crime',
            'severity' => 'medium',
            'occurred_at' => now()->subDays(14),
            'detected_at' => now()->subDays(7),
            'reporter_external_source' => 'MLRO_email',
            'is_cyber_incident' => false,
            'is_data_breach' => false,
            'affects_customers' => false,
            'assigned_to' => $compliance?->id,
        ], $reporter?->id ?? 1);

        $service->transition($pep, 'triaged', $reporter?->id ?? 1);
        $service->transition($pep, 'investigating', $reporter?->id ?? 1);

        IncidentAction::create([
            'tenant_id' => 1,
            'incident_id' => $pep->id,
            'type' => 'corrective',
            'title' => 'Enhanced due diligence on PEP account',
            'description' => 'Conduct retroactive EDD and assess if account activity warrants SAR filing.',
            'owner_user_id' => $compliance?->id,
            'due_at' => now()->addDays(3)->toDateString(),
        ]);

        IncidentAction::create([
            'tenant_id' => 1,
            'incident_id' => $pep->id,
            'type' => 'preventive',
            'title' => 'Review and update sanctions screening ruleset',
            'description' => 'Engage vendor to update PEP list feeds and test screening engine configuration.',
            'owner_user_id' => $assignee?->id,
            'due_at' => now()->addDays(14)->toDateString(),
        ]);

        // 8 — Lost laptop with customer data (data_breach, medium, NDPC pending)
        $laptop = $service->create([
            'title' => 'Lost laptop containing customer data — Abuja RO',
            'description' => 'Laptop belonging to a relationship manager was reported stolen at Abuja Regional Office. Device contained unencrypted customer records for 85 clients.',
            'category' => 'data_breach',
            'severity' => 'medium',
            'occurred_at' => now()->subDays(2),
            'detected_at' => now()->subDays(1),
            'is_cyber_incident' => false,
            'is_data_breach' => true,
            'affects_customers' => true,
            'affected_customer_count' => 85,
            'assigned_to' => $compliance?->id,
        ], $reporter?->id ?? 1);

        $service->transition($laptop, 'triaged', $reporter?->id ?? 1);

        // NDPC notification is already created by scheduleRegulatorNotifications — it's pending
        // Add some actions
        IncidentAction::create([
            'tenant_id' => 1,
            'incident_id' => $laptop->id,
            'type' => 'corrective',
            'title' => 'Remote wipe laptop',
            'description' => 'Attempt remote wipe via MDM if device connects to internet.',
            'owner_user_id' => $tester?->id,
            'due_at' => now()->toDateString(),
        ]);

        $service->attachEvidence($laptop, [
            'type' => 'email',
            'title' => 'Police report — stolen laptop',
            'description' => 'Copy of police report filed at Abuja Central Police Station.',
        ], $reporter?->id ?? 1);
    }
}
