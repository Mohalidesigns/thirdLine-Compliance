<?php

declare(strict_types=1);

namespace Modules\Audit\Filament\Resources\AuditEventResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Audit\Filament\Resources\AuditEventResource;

class ListAuditEvents extends ListRecords
{
    protected static string $resource = AuditEventResource::class;

    /** No create button — audit events are append-only. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
