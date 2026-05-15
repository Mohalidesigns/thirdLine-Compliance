<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources\ReturnApprovalResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Returns\Filament\Resources\ReturnApprovalResource;

class ListReturnApprovals extends ListRecords
{
    protected static string $resource = ReturnApprovalResource::class;
}
