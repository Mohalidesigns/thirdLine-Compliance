<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources\ReturnRunResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Returns\Filament\Resources\ReturnRunResource;

class ListReturnRuns extends ListRecords
{
    protected static string $resource = ReturnRunResource::class;
}
