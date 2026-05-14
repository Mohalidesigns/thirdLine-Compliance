<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\RegulatorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\RegulatorResource;

class ListRegulators extends ListRecords
{
    protected static string $resource = RegulatorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
