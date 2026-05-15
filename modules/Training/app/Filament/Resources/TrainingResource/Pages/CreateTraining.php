<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\TrainingResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Training\Filament\Resources\TrainingResource;

class CreateTraining extends CreateRecord
{
    protected static string $resource = TrainingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
