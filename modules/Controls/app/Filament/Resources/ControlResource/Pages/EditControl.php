<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\ControlResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Controls\Filament\Resources\ControlResource;

class EditControl extends EditRecord
{
    protected static string $resource = ControlResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
