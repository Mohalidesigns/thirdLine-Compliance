<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\CertificationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Training\Filament\Resources\CertificationResource;

class CreateCertification extends CreateRecord
{
    protected static string $resource = CertificationResource::class;
}
