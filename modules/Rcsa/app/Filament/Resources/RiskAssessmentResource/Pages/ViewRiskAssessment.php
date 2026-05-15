<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskAssessmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Rcsa\Filament\Resources\RiskAssessmentResource;

class ViewRiskAssessment extends ViewRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
