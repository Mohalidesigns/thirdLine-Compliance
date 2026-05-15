<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\IssueResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Controls\Filament\Resources\IssueResource;

class ViewIssue extends ViewRecord
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
