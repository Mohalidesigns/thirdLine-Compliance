<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Returns\Filament\Resources\ReturnApprovalResource\Pages;
use Modules\Returns\Models\ReturnApproval;

class ReturnApprovalResource extends Resource
{
    protected static ?string $model = ReturnApproval::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|\UnitEnum|null $navigationGroup = 'Returns';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('run.definition.code')
                    ->label('Return Code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('run.period_label')
                    ->label('Period'),
                Tables\Columns\TextColumn::make('step')
                    ->badge()
                    ->formatStateUsing(fn (ReturnApproval $record) => $record->stepLabel())
                    ->color(fn ($state) => match ($state) {
                        'maker_submit' => 'info',
                        'checker_review' => 'warning',
                        'approver_sign_off' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Actor'),
                Tables\Columns\TextColumn::make('decision')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'submitted' => 'info',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('acted_at')
                    ->label('Acted At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('step')
                    ->options([
                        'maker_submit' => 'Maker Submit',
                        'checker_review' => 'Checker Review',
                        'approver_sign_off' => 'Approver Sign-Off',
                    ]),
                Tables\Filters\SelectFilter::make('decision')
                    ->options([
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->defaultSort('acted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnApprovals::route('/'),
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
}
