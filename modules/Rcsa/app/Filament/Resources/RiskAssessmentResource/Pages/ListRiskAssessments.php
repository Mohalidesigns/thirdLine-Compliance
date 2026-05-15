<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskAssessmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Rcsa\Filament\Resources\RiskAssessmentResource;

class ListRiskAssessments extends ListRecords
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
