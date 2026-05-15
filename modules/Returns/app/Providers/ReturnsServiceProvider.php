<?php

declare(strict_types=1);

namespace Modules\Returns\Providers;

use Modules\Returns\Console\Commands\ReturnsScheduleCommand;
use Modules\Returns\Services\StrategyRegistry;
use Modules\Returns\Strategies\CbnEfassStubStrategy;
use Modules\Returns\Strategies\ManualStubStrategy;
use Modules\Returns\Strategies\NfiuGoAmlStubStrategy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ReturnsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Returns';

    protected string $nameLower = 'returns';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Register the strategy registry as a singleton and wire up strategies.
        $this->app->singleton(StrategyRegistry::class, function (): StrategyRegistry {
            $registry = new StrategyRegistry;

            // Wire stub strategies. Add real implementations here in Phase 2.
            $registry->register('nfiu:goaml_xml', NfiuGoAmlStubStrategy::class);
            $registry->register('cbn:cbn_efass', CbnEfassStubStrategy::class);

            // Manual channel: used as fallback for all regulators on 'manual' channel
            $registry->register('*:manual', ManualStubStrategy::class);
            $registry->register('*:portal', ManualStubStrategy::class);
            $registry->register('*:email', ManualStubStrategy::class);
            $registry->register('*:sftp', ManualStubStrategy::class);
            $registry->register('*:api', ManualStubStrategy::class);
            $registry->register('*:firs_tax_pro_max', ManualStubStrategy::class);

            return $registry;
        });
    }

    public function boot(): void
    {
        parent::boot();
        $this->commands([ReturnsScheduleCommand::class]);
    }
}
