<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources\ReturnDefinitionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Returns\Filament\Resources\ReturnDefinitionResource;

class ViewReturnDefinition extends ViewRecord
{
    protected static string $resource = ReturnDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
