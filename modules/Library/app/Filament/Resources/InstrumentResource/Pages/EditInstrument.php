<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\InstrumentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Library\Filament\Resources\InstrumentResource;

class EditInstrument extends EditRecord
{
    protected static string $resource = InstrumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
