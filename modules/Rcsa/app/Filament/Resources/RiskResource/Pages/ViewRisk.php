<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Rcsa\Filament\Resources\RiskResource;

class ViewRisk extends ViewRecord
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
