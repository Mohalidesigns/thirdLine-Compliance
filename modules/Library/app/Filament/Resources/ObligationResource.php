<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Library\Filament\Resources\ObligationResource\Pages;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\Obligation;

class ObligationResource extends Resource
{
    protected static ?string $model = Obligation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Obligation Details')->schema([
                Forms\Components\Select::make('instrument_id')
                    ->label('Instrument')
                    ->options(fn () => Instrument::orderBy('source_title')->pluck('source_title', 'id'))
                    ->required()
                    ->searchable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(50),
                Forms\Components\TextInput::make('responsible_team')
                    ->maxLength(200),
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'satisfied' => 'Satisfied',
                        'overdue' => 'Overdue',
                        'waived' => 'Waived',
                    ])
                    ->required(),
                Forms\Components\Select::make('due_basis')
                    ->options([
                        'recurring' => 'Recurring',
                        'one_off' => 'One-off',
                        'triggered' => 'Triggered',
                    ])
                    ->required(),
                Forms\Components\Select::make('frequency')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi_annual' => 'Semi-Annual',
                        'annual' => 'Annual',
                    ]),
                Forms\Components\DatePicker::make('next_due_date')
                    ->label('Next Due Date'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('instrument.source_title')
                    ->label('Instrument')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('due_basis')
                    ->label('Basis')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'open' => 'primary',
                        'satisfied' => 'success',
                        'overdue' => 'danger',
                        'waived' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_due_date')
                    ->label('Next Due')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('responsible_team')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'satisfied' => 'Satisfied', 'overdue' => 'Overdue', 'waived' => 'Waived']),
                Tables\Filters\SelectFilter::make('due_basis')
                    ->options(['recurring' => 'Recurring', 'one_off' => 'One-off', 'triggered' => 'Triggered']),
            ])
            ->defaultSort('next_due_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListObligations::route('/'),
            'create' => Pages\CreateObligation::route('/create'),
            'edit' => Pages\EditObligation::route('/{record}/edit'),
            'view' => Pages\ViewObligation::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'compliance_officer', 'auditor']) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'compliance_officer', 'auditor']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'compliance_officer']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'compliance_officer']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'compliance_officer']) ?? false;
    }
}
