<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Library\Filament\Resources\InstrumentResource\Pages;
use Modules\Library\Models\AreaOfFocus;
use Modules\Library\Models\Instrument;
use Modules\Library\Models\InstrumentType;
use Modules\Library\Models\Nature;
use Modules\Library\Models\Regulator;
use Modules\Library\Models\RiskRating;
use Modules\Library\Models\Status;

class InstrumentResource extends Resource
{
    protected static ?string $model = Instrument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Library';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Instrument';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Basic Information')->schema([
                Forms\Components\TextInput::make('source_title')
                    ->label('Title')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('objectives')
                    ->label('Objectives')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Classification')->schema([
                Forms\Components\Select::make('regulator_id')
                    ->label('Regulator')
                    ->options(fn () => Regulator::orderBy('code')->pluck('code', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('instrument_type_id')
                    ->label('Instrument Type')
                    ->options(fn () => InstrumentType::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('nature_id')
                    ->label('Nature')
                    ->options(fn () => Nature::orderBy('name')->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('status_id')
                    ->label('Status')
                    ->options(fn () => Status::orderBy('name')->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('area_of_focus_id')
                    ->label('Area of Focus')
                    ->options(fn () => AreaOfFocus::orderBy('name')->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('risk_rating_id')
                    ->label('Risk Rating')
                    ->options(fn () => RiskRating::orderBy('id')->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('applicability')
                    ->options(['Yes' => 'Yes', 'No' => 'No', 'Partially' => 'Partially'])
                    ->required(),
            ])->columns(2),
            Forms\Components\Section::make('Dates & Links')->schema([
                Forms\Components\DatePicker::make('date_issue')->label('Issue Date'),
                Forms\Components\DatePicker::make('date_commence')->label('Commencement Date'),
                Forms\Components\DatePicker::make('date_repeal')->label('Repeal Date'),
                Forms\Components\TextInput::make('link_url')->label('Source URL')->url()->maxLength(2048),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source_title')
                    ->label('Title')
                    ->searchable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('regulator.code')
                    ->label('Regulator')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('instrumentType.name')
                    ->label('Type')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('riskRating.name')
                    ->label('Risk')
                    ->badge()
                    ->color(fn ($record) => match ($record?->riskRating?->name) {
                        'High' => 'danger',
                        'Medium' => 'warning',
                        'Low' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('date_commence')
                    ->label('Effective')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('regulator_id')
                    ->label('Regulator')
                    ->options(fn () => Regulator::orderBy('code')->pluck('code', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('instrument_type_id')
                    ->label('Type')
                    ->options(fn () => InstrumentType::orderBy('name')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('risk_rating_id')
                    ->label('Risk Rating')
                    ->options(fn () => RiskRating::orderBy('id')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(fn () => Status::orderBy('name')->pluck('name', 'id')),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstruments::route('/'),
            'create' => Pages\CreateInstrument::route('/create'),
            'edit' => Pages\EditInstrument::route('/{record}/edit'),
            'view' => Pages\ViewInstrument::route('/{record}'),
        ];
    }
}
