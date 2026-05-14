<?php

namespace Modules\Policy\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PolicyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Policy';

    protected string $nameLower = 'policy';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
