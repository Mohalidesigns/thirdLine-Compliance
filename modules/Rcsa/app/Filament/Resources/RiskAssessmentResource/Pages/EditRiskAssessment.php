<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources\RiskAssessmentResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Rcsa\Filament\Resources\RiskAssessmentResource;

class EditRiskAssessment extends EditRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
