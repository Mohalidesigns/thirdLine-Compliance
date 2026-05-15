<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\ControlResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Controls\Filament\Resources\ControlResource;

class ListControls extends ListRecords
{
    protected static string $resource = ControlResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
