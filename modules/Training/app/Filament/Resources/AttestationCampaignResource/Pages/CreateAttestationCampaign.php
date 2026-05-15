<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\AttestationCampaignResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Training\Filament\Resources\AttestationCampaignResource;

class CreateAttestationCampaign extends CreateRecord
{
    protected static string $resource = AttestationCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
