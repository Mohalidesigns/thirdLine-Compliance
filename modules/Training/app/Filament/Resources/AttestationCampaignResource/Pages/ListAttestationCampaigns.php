<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\AttestationCampaignResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Training\Filament\Resources\AttestationCampaignResource;

class ListAttestationCampaigns extends ListRecords
{
    protected static string $resource = AttestationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
