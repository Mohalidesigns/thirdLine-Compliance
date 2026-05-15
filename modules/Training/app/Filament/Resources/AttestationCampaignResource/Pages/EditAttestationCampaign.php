<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\AttestationCampaignResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Training\Filament\Resources\AttestationCampaignResource;

class EditAttestationCampaign extends EditRecord
{
    protected static string $resource = AttestationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
