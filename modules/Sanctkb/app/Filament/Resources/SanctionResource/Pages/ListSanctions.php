<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Filament\Resources\SanctionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Sanctkb\Filament\Resources\SanctionResource;

class ListSanctions extends ListRecords
{
    protected static string $resource = SanctionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
