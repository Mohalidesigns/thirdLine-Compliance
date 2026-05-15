<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Controls\Filament\Resources\ControlTestResource\Pages;
use Modules\Controls\Models\ControlTest;

class ControlTestResource extends Resource
{
    protected static ?string $model = ControlTest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Controls';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'Control Test';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Test Details')->schema([
                Forms\Components\Select::make('control_id')
                    ->label('Control')
                    ->relationship('control', 'title')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('outcome')
                    ->options(['passed' => 'Passed', 'partial' => 'Partial', 'failed' => 'Failed', 'not_applicable' => 'N/A'])
                    ->required(),
                Forms\Components\DateTimePicker::make('tested_at')->required()->default(now()),
                Forms\Components\TextInput::make('population_size')->numeric()->minValue(1),
                Forms\Components\TextInput::make('sample_size')->numeric()->minValue(1),
                Forms\Components\TextInput::make('evidence_url')->url()->maxLength(500)->columnSpanFull(),
                Forms\Components\Textarea::make('findings')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('control.reference')->label('Control Ref')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('control.title')->label('Control')->limit(40),
                Tables\Columns\TextColumn::make('outcome')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'passed' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        'not_applicable' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sample_size')->label('Sample'),
                Tables\Columns\TextColumn::make('tested_at')->dateTime('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('tester.name')->label('Tested By')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('outcome')
                    ->options(['passed' => 'Passed', 'partial' => 'Partial', 'failed' => 'Failed', 'not_applicable' => 'N/A']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('tested_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListControlTests::route('/'),
            'create' => Pages\CreateControlTest::route('/create'),
            'view' => Pages\ViewControlTest::route('/{record}'),
        ];
    }
}
