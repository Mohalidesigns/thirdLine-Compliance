<?php

declare(strict_types=1);

namespace Modules\Incident\Console\Commands;

use App\Services\AuditWriter;
use Illuminate\Console\Command;
use Modules\Incident\Models\IncidentNotification;

class IncidentScanNotificationsCommand extends Command
{
    protected $signature = 'incidents:scan-notifications';

    protected $description = 'Flag pending regulator notifications whose deadline has passed as overdue.';

    public function __construct(
        private readonly AuditWriter $auditWriter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $overdue = IncidentNotification::withoutGlobalScopes()
            ->where('status', 'pending')
            ->where('deadline_at', '<', now())
            ->with('incident:id,tenant_id,code')
            ->get();

        $count = 0;

        foreach ($overdue as $notification) {
            $notification->update(['status' => 'overdue']);

            if ($notification->incident !== null) {
                $this->auditWriter->record(
                    action: 'incident.notification_overdue',
                    subject: $notification->incident,
                    context: [
                        'notification_id' => $notification->id,
                        'regulator' => $notification->regulator,
                        'deadline_at' => $notification->deadline_at->toIso8601String(),
                    ],
                );
            }

            $count++;
        }

        $this->info("Flagged {$count} overdue notification(s).");

        return self::SUCCESS;
    }
}
