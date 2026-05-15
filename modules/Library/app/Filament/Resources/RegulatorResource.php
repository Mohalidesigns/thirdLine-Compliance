<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Library\Filament\Resources\RegulatorResource\Pages;
use Modules\Library\Models\Regulator;

class RegulatorResource extends Resource
{
    protected static ?string $model = Regulator::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('code')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(200),
            Forms\Components\TextInput::make('country')
                ->default('NGA')
                ->maxLength(10),
            Forms\Components\TextInput::make('website_url')
                ->url()
                ->maxLength(500),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->badge(),
                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')
                    ->url(fn ($record) => $record->website_url)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('code', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegulators::route('/'),
            'create' => Pages\CreateRegulator::route('/create'),
            'edit' => Pages\EditRegulator::route('/{record}/edit'),
            'view' => Pages\ViewRegulator::route('/{record}'),
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
