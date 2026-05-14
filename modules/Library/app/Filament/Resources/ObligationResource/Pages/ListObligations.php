<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\ObligationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\ObligationResource;

class ListObligations extends ListRecords
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
