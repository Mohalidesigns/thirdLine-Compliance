<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\IssueResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Controls\Filament\Resources\IssueResource;

class ListIssues extends ListRecords
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
