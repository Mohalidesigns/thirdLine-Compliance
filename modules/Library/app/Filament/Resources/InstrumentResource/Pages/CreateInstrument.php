<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\InstrumentResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Library\Filament\Resources\InstrumentResource;

class CreateInstrument extends CreateRecord
{
    protected static string $resource = InstrumentResource::class;
}
