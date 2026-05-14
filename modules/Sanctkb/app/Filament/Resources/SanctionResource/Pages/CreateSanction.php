<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Filament\Resources\SanctionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Sanctkb\Filament\Resources\SanctionResource;

class CreateSanction extends CreateRecord
{
    protected static string $resource = SanctionResource::class;
}
