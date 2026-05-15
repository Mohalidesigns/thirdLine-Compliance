<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\TrainingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Training\Filament\Resources\TrainingResource;

class ViewTraining extends ViewRecord
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
