<?php

namespace Modules\Controls\Providers;

use Modules\Controls\Console\Commands\RunCcmRulesCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ControlsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Controls';

    protected string $nameLower = 'controls';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->commands([RunCcmRulesCommand::class]);
    }
}
