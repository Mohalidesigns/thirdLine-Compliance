<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Rcsa\Filament\Resources\RiskResource;

class CreateRisk extends CreateRecord
{
    protected static string $resource = RiskResource::class;
}
