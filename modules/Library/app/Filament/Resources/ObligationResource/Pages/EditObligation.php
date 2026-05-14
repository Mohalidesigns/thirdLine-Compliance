<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\ObligationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Library\Filament\Resources\ObligationResource;

class EditObligation extends EditRecord
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
