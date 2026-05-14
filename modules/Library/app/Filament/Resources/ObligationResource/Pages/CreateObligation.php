<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\ObligationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Library\Filament\Resources\ObligationResource;

class CreateObligation extends CreateRecord
{
    protected static string $resource = ObligationResource::class;
}
