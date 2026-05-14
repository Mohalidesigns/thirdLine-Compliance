<?php

declare(strict_types=1);

namespace Modules\Policy\Filament\Resources\PolicyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Modules\Policy\Filament\Resources\PolicyResource;

class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->state::$name === 'draft'),
        ];
    }
}
