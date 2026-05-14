<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\ObligationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Library\Filament\Resources\ObligationResource;

class ViewObligation extends ViewRecord
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
