<?php

namespace Modules\Incident\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Incident\Models\IncidentEvidence;
use Modules\Incident\Observers\IncidentEvidenceObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    public function boot(): void
    {
        parent::boot();
        IncidentEvidence::observe(IncidentEvidenceObserver::class);
    }
}
