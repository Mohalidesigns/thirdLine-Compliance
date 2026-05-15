<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\ControlResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Controls\Filament\Resources\ControlResource;

class ViewControl extends ViewRecord
{
    protected static string $resource = ControlResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
