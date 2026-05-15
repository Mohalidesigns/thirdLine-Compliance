<?php

namespace Modules\Rcsa\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class RcsaServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Rcsa';

    protected string $nameLower = 'rcsa';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
