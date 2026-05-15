<?php

declare(strict_types=1);

namespace Modules\Audit\Filament\Resources\AuditEventResource\Pages;

use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Audit\Filament\Resources\AuditEventResource;

class ViewAuditEvent extends ViewRecord
{
    protected static string $resource = AuditEventResource::class;

    /** No edit action — the record is immutable. */
    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Event Details')->schema([
                TextEntry::make('recorded_at')
                    ->label('Recorded At')
                    ->dateTime('d M Y H:i:s'),

                TextEntry::make('action')
                    ->label('Action')
                    ->badge(),

                TextEntry::make('actor.name')
                    ->label('User')
                    ->default('System'),

                TextEntry::make('actor_type')
                    ->label('Actor Type'),

                TextEntry::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),

                TextEntry::make('subject_id')
                    ->label('Subject ID'),

                TextEntry::make('tenant_id')
                    ->label('Tenant ID'),
            ])->columns(2),

            Section::make('Context & Changes')
                ->description('Raw JSON stored with this event. The "changes" key (when present) contains before/after diffs.')
                ->schema([
                    TextEntry::make('context')
                        ->label('Context')
                        ->formatStateUsing(
                            fn (mixed $state): string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : (string) $state
                        )
                        ->fontFamily('mono')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
