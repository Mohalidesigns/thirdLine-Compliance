<?php

declare(strict_types=1);

namespace Modules\Policy\Filament\Resources\PolicyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Policy\Filament\Resources\PolicyResource;

class CreatePolicy extends CreateRecord
{
    protected static string $resource = PolicyResource::class;
}
