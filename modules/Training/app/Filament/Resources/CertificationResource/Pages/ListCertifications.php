<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\CertificationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Training\Filament\Resources\CertificationResource;

class ListCertifications extends ListRecords
{
    protected static string $resource = CertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
