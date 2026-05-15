<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\CertificationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Training\Filament\Resources\CertificationResource;

class ViewCertification extends ViewRecord
{
    protected static string $resource = CertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
