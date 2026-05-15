<?php

declare(strict_types=1);

namespace Modules\Audit\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Audit\Filament\Resources\AuditEventResource\Pages;
use Modules\Audit\Models\AuditEvent;

/**
 * Filament admin resource for viewing audit events.
 *
 * Read-only — create and edit are disabled. The table is append-only at the
 * database level; this resource only exposes the list and view pages.
 *
 * Eager-loads actor (users) to prevent N+1 on the list page.
 */
class AuditEventResource extends Resource
{
    protected static ?string $model = AuditEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $label = 'Audit Event';

    protected static ?string $pluralLabel = 'Audit Events';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    // Audit log is append-only — create, edit, and delete are permanently disabled.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // Eager-load actor to prevent N+1 on list rendering.
                AuditEvent::query()->with('actor')->orderByDesc('recorded_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Recorded At')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actor.name')
                    ->label('User')
                    ->default('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->color(static function (string $state): string {
                        return match (true) {
                            str_contains($state, 'created') => 'success',
                            str_contains($state, 'updated') => 'info',
                            str_contains($state, 'deleted') => 'danger',
                            str_contains($state, 'state_transitioned') => 'warning',
                            str_contains($state, 'restored') => 'success',
                            default => 'gray',
                        };
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actor_type')
                    ->label('Actor Type')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'system' ? 'gray' : 'primary')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Action')
                    ->options(static function (): array {
                        return AuditEvent::query()
                            ->select('action')
                            ->distinct()
                            ->orderBy('action')
                            ->pluck('action', 'action')
                            ->toArray();
                    })
                    ->searchable(),

                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(static function (): array {
                        return AuditEvent::query()
                            ->select('subject_type')
                            ->whereNotNull('subject_type')
                            ->distinct()
                            ->orderBy('subject_type')
                            ->pluck('subject_type', 'subject_type')
                            ->mapWithKeys(fn (string $fqn): array => [$fqn => class_basename($fqn)])
                            ->toArray();
                    }),

                SelectFilter::make('actor_id')
                    ->label('User')
                    ->relationship('actor', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('recorded_at')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, string $date): Builder => $q->where('recorded_at', '>=', Carbon::parse($date)->startOfDay()),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, string $date): Builder => $q->where('recorded_at', '<=', Carbon::parse($date)->endOfDay()),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditEvents::route('/'),
            'view' => Pages\ViewAuditEvent::route('/{record}'),
        ];
    }
}
