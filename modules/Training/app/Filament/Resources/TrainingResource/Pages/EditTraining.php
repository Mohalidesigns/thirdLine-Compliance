<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\TrainingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Training\Filament\Resources\TrainingResource;

class EditTraining extends EditRecord
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
