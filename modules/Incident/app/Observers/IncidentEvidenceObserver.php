<?php

declare(strict_types=1);

namespace Modules\Incident\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Incident\Models\IncidentEvidence;

class IncidentEvidenceObserver
{
    public function created(IncidentEvidence $evidence): void
    {
        DB::table('incidents')
            ->where('id', $evidence->incident_id)
            ->increment('closure_evidence_count');
    }

    public function deleted(IncidentEvidence $evidence): void
    {
        DB::table('incidents')
            ->where('id', $evidence->incident_id)
            ->where('closure_evidence_count', '>', 0)
            ->decrement('closure_evidence_count');
    }
}
