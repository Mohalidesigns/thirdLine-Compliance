<?php

namespace Modules\Training\Providers;

use Modules\Training\Console\Commands\TrainingCheckOverdueCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class TrainingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Training';

    protected string $nameLower = 'training';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
        $this->commands([TrainingCheckOverdueCommand::class]);
    }
}
