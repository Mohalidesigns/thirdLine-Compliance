<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Rcsa\Filament\Resources\RiskResource;

class ListRisks extends ListRecords
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
