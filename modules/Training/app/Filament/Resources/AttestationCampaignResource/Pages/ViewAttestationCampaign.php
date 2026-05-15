<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\AttestationCampaignResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Training\Filament\Resources\AttestationCampaignResource;

class ViewAttestationCampaign extends ViewRecord
{
    protected static string $resource = AttestationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
