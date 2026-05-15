<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\ControlResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Controls\Filament\Resources\ControlResource;

class CreateControl extends CreateRecord
{
    protected static string $resource = ControlResource::class;
}
