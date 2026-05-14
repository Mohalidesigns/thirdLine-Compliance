<?php

declare(strict_types=1);

namespace Modules\Sanctkb\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Library\Models\Regulator;
use Modules\Sanctkb\Filament\Resources\SanctionResource\Pages;
use Modules\Sanctkb\Models\Sanction;

class SanctionResource extends Resource
{
    protected static ?string $model = Sanction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Sanctions';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Sanction Details')->schema([
                Forms\Components\Select::make('regulator_id')
                    ->label('Regulator')
                    ->options(fn () => Regulator::orderBy('code')->pluck('code', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(100),
                Forms\Components\TextInput::make('section')
                    ->label('Legal Section')
                    ->maxLength(200),
                Forms\Components\TextInput::make('party_name')
                    ->label('Party Name')
                    ->required()
                    ->maxLength(500),
                Forms\Components\Select::make('party_type')
                    ->options([
                        'individual' => 'Individual',
                        'corporate' => 'Corporate',
                        'bank' => 'Bank',
                        'mfb' => 'MFB',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\Select::make('penalty_type')
                    ->options([
                        'fine' => 'Fine',
                        'suspension' => 'Suspension',
                        'revocation' => 'Revocation',
                        'prohibition' => 'Prohibition',
                        'caution' => 'Caution',
                    ])
                    ->required(),
            ])->columns(2),
            Forms\Components\Section::make('Offence & Amount')->schema([
                Forms\Components\Textarea::make('offence')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amount_naira')
                    ->label('Penalty Amount (NGN)')
                    ->numeric()
                    ->prefix('₦'),
                Forms\Components\DatePicker::make('effective_date')
                    ->label('Sanction Date'),
                Forms\Components\TextInput::make('source_url')
                    ->label('Source URL')
                    ->url()
                    ->maxLength(2048),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('party_name')
                    ->label('Party')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('regulator.code')
                    ->label('Regulator')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('penalty_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'revocation' => 'danger',
                        'suspension' => 'warning',
                        'fine' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount_naira')
                    ->label('Amount (NGN)')
                    ->numeric(decimalPlaces: 0)
                    ->prefix('₦')
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('offence')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('regulator_id')
                    ->label('Regulator')
                    ->options(fn () => Regulator::orderBy('code')->pluck('code', 'id')),
                Tables\Filters\SelectFilter::make('party_type')
                    ->options([
                        'individual' => 'Individual',
                        'corporate' => 'Corporate',
                        'bank' => 'Bank',
                        'mfb' => 'MFB',
                    ]),
                Tables\Filters\SelectFilter::make('penalty_type')
                    ->options([
                        'fine' => 'Fine',
                        'suspension' => 'Suspension',
                        'revocation' => 'Revocation',
                    ]),
            ])
            ->searchable()
            ->defaultSort('effective_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSanctions::route('/'),
            'create' => Pages\CreateSanction::route('/create'),
            'edit' => Pages\EditSanction::route('/{record}/edit'),
            'view' => Pages\ViewSanction::route('/{record}'),
        ];
    }
}
