<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\ControlTestResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Controls\Filament\Resources\ControlTestResource;

class CreateControlTest extends CreateRecord
{
    protected static string $resource = ControlTestResource::class;
}
