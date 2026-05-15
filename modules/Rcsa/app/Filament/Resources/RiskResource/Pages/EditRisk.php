<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Rcsa\Filament\Resources\RiskResource;

class EditRisk extends EditRecord
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
