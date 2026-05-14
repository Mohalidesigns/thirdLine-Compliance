<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\RegulatorResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Library\Filament\Resources\RegulatorResource;

class EditRegulator extends EditRecord
{
    protected static string $resource = RegulatorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
