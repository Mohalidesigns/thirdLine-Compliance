<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Returns\Filament\Resources\ReturnRunResource\Pages;
use Modules\Returns\Models\ReturnDefinition;
use Modules\Returns\Models\ReturnRun;
use Modules\Returns\Services\ReturnsService;

class ReturnRunResource extends Resource
{
    protected static ?string $model = ReturnRun::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|\UnitEnum|null $navigationGroup = 'Returns';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('definition.code')
                    ->label('Code')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('definition.title')
                    ->label('Return')
                    ->limit(50),
                Tables\Columns\TextColumn::make('definition.regulator')
                    ->label('Regulator')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReturnDefinition::regulatorLabels()[$state] ?? strtoupper($state)),
                Tables\Columns\TextColumn::make('period_label')
                    ->label('Period'),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ReturnRun $record) => $record->statusLabel())
                    ->color(fn (ReturnRun $record) => $record->statusColor()),
                Tables\Columns\TextColumn::make('maker.name')
                    ->label('Maker')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('checker.name')
                    ->label('Checker')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('submission_reference')
                    ->label('Reference')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->dateTime('d M Y')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'submitted_pending_ack' => 'Pending Acknowledgement',
                        'acknowledged' => 'Acknowledged',
                        'late' => 'Late',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('regulator')
                    ->relationship('definition', 'regulator')
                    ->options(ReturnDefinition::regulatorLabels()),
                Tables\Filters\SelectFilter::make('frequency')
                    ->relationship('definition', 'frequency')
                    ->options(ReturnDefinition::frequencyLabels()),
                Tables\Filters\Filter::make('due_at')
                    ->form([
                        DatePicker::make('due_from')->label('Due From'),
                        DatePicker::make('due_until')->label('Due Until'),
                    ])
                    ->query(function ($query, array $data) {
                        $query
                            ->when($data['due_from'], fn ($q) => $q->where('due_at', '>=', $data['due_from']))
                            ->when($data['due_until'], fn ($q) => $q->where('due_at', '<=', $data['due_until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_late')
                    ->label('Mark Late')
                    ->icon('heroicon-o-exclamation-circle')
                    ->color('danger')
                    ->visible(fn (ReturnRun $record) => in_array($record->status, ['scheduled', 'in_progress'], true))
                    ->requiresConfirmation()
                    ->action(fn (ReturnRun $record) => app(ReturnsService::class)->markLate($record)),
            ])
            ->defaultSort('due_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRuns::route('/'),
            'view' => Pages\ViewReturnRun::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('returns.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('returns.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false; // Runs are created by the scheduler only
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Read-only in admin; workflow happens in Inertia
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('returns.manage') ?? false;
    }
}
