<?php

namespace Modules\Incident\Providers;

use Modules\Incident\Console\Commands\IncidentScanNotificationsCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class IncidentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Incident';

    protected string $nameLower = 'incident';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->commands([IncidentScanNotificationsCommand::class]);
    }
}
