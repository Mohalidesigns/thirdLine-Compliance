<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources\ReturnDefinitionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Returns\Filament\Resources\ReturnDefinitionResource;

class CreateReturnDefinition extends CreateRecord
{
    protected static string $resource = ReturnDefinitionResource::class;
}
