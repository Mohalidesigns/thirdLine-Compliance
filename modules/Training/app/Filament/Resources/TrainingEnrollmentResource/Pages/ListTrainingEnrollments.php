<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\TrainingEnrollmentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Training\Filament\Resources\TrainingEnrollmentResource;

class ListTrainingEnrollments extends ListRecords
{
    protected static string $resource = TrainingEnrollmentResource::class;
}
