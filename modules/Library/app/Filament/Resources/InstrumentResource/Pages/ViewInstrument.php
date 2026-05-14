<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\InstrumentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Library\Filament\Resources\InstrumentResource;

class ViewInstrument extends ViewRecord
{
    protected static string $resource = InstrumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
