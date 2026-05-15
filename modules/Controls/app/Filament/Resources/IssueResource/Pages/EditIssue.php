<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\IssueResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Controls\Filament\Resources\IssueResource;

class EditIssue extends EditRecord
{
    protected static string $resource = IssueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
