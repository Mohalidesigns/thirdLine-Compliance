<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources\ReturnDefinitionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Returns\Filament\Resources\ReturnDefinitionResource;

class ListReturnDefinitions extends ListRecords
{
    protected static string $resource = ReturnDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
