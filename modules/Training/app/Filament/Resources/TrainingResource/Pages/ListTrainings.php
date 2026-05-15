<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\TrainingResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Training\Filament\Resources\TrainingResource;

class ListTrainings extends ListRecords
{
    protected static string $resource = TrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
