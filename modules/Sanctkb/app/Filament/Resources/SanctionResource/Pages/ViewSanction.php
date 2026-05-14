<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Filament\Resources\SanctionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Sanctkb\Filament\Resources\SanctionResource;

class ViewSanction extends ViewRecord
{
    protected static string $resource = SanctionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
