<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\CertificationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Training\Filament\Resources\CertificationResource;

class EditCertification extends EditRecord
{
    protected static string $resource = CertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
