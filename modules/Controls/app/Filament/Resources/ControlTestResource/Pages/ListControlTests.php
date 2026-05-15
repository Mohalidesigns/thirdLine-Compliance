<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\ControlTestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Controls\Filament\Resources\ControlTestResource;

class ListControlTests extends ListRecords
{
    protected static string $resource = ControlTestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
