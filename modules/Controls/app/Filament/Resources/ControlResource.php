<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Controls\Filament\Resources\ControlResource\Pages;
use Modules\Controls\Models\Control;

class ControlResource extends Resource
{
    protected static ?string $model = Control::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Controls';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Control Details')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('control_type')
                    ->label('Type')
                    ->options([
                        'preventive' => 'Preventive',
                        'detective' => 'Detective',
                        'corrective' => 'Corrective',
                        'compensating' => 'Compensating',
                    ])
                    ->required(),
                Forms\Components\Select::make('nature')
                    ->options([
                        'manual' => 'Manual',
                        'automated' => 'Automated',
                        'hybrid' => 'Hybrid',
                    ])
                    ->required(),
                Forms\Components\Select::make('frequency')
                    ->options([
                        'continuous' => 'Continuous',
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semiannual' => 'Semi-Annual',
                        'annual' => 'Annual',
                        'event_driven' => 'Event-Driven',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('owner_team')
                    ->required()
                    ->maxLength(200),
                Forms\Components\Select::make('status')
                    ->options(['active' => 'Active', 'draft' => 'Draft', 'deprecated' => 'Deprecated'])
                    ->default('active')
                    ->required(),
                Forms\Components\DatePicker::make('next_test_due')->label('Next Test Due'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('control_type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'preventive' => 'success',
                        'detective' => 'info',
                        'corrective' => 'warning',
                        'compensating' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('frequency')->badge(),
                Tables\Columns\TextColumn::make('owner_team')->label('Owner')->toggleable()->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'deprecated' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_test_due')->date('d M Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('last_tested_at')->dateTime('d M Y')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('control_type')
                    ->options(['preventive' => 'Preventive', 'detective' => 'Detective', 'corrective' => 'Corrective', 'compensating' => 'Compensating']),
                Tables\Filters\SelectFilter::make('frequency')
                    ->options(['continuous' => 'Continuous', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'semiannual' => 'Semi-Annual', 'annual' => 'Annual']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['active' => 'Active', 'draft' => 'Draft', 'deprecated' => 'Deprecated']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('reference', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListControls::route('/'),
            'create' => Pages\CreateControl::route('/create'),
            'edit' => Pages\EditControl::route('/{record}/edit'),
            'view' => Pages\ViewControl::route('/{record}'),
        ];
    }
}
