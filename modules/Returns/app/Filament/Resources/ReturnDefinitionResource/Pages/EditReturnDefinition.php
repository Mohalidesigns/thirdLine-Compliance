<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources\ReturnDefinitionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Returns\Filament\Resources\ReturnDefinitionResource;

class EditReturnDefinition extends EditRecord
{
    protected static string $resource = ReturnDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
