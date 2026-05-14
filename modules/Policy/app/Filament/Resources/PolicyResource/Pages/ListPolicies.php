<?php

declare(strict_types=1);

namespace Modules\Policy\Filament\Resources\PolicyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Policy\Filament\Resources\PolicyResource;

class ListPolicies extends ListRecords
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
