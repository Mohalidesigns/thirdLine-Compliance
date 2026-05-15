<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources\IssueResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Controls\Filament\Resources\IssueResource;

class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;
}
