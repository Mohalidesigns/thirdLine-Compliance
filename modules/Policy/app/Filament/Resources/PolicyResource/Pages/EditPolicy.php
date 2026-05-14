<?php

declare(strict_types=1);

namespace Modules\Policy\Filament\Resources\PolicyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Policy\Filament\Resources\PolicyResource;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->state::$name === 'draft'),
        ];
    }
}
