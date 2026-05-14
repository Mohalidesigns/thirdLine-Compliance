<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Filament\Resources\SanctionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Sanctkb\Filament\Resources\SanctionResource;

class EditSanction extends EditRecord
{
    protected static string $resource = SanctionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
