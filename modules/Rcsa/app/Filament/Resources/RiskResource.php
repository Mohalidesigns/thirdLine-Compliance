<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Rcsa\Filament\Resources\RiskResource\Pages;
use Modules\Rcsa\Models\Risk;

class RiskResource extends Resource
{
    protected static ?string $model = Risk::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Risk';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Risk Details')->schema([
                Forms\Components\Select::make('cycle_id')
                    ->label('Assessment Cycle')
                    ->relationship('cycle', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->options([
                        'operational' => 'Operational',
                        'credit' => 'Credit',
                        'market' => 'Market',
                        'liquidity' => 'Liquidity',
                        'compliance' => 'Compliance',
                        'reputational' => 'Reputational',
                        'strategic' => 'Strategic',
                        'cyber' => 'Cyber',
                        'aml' => 'AML & CFT',
                        'conduct' => 'Conduct',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('risk_owner')->maxLength(200),
                Forms\Components\TextInput::make('inherent_likelihood')
                    ->label('Inherent Likelihood (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Forms\Components\TextInput::make('inherent_impact')
                    ->label('Inherent Impact (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Forms\Components\TextInput::make('residual_likelihood')
                    ->label('Residual Likelihood (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Forms\Components\TextInput::make('residual_impact')
                    ->label('Residual Impact (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Forms\Components\Textarea::make('mitigation_summary')->rows(3)->columnSpanFull(),
                Forms\Components\Textarea::make('accept_basis')->label('Accept Basis')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'aml' => 'AML & CFT',
                        'cyber' => 'Cyber',
                        default => ucfirst((string) $state),
                    }),
                Tables\Columns\TextColumn::make('inherent_score')->label('Inh.Score'),
                Tables\Columns\TextColumn::make('residual_score')->label('Res.Score'),
                Tables\Columns\TextColumn::make('residual_rating')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('cycle.name')->label('Cycle')->toggleable()->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'operational' => 'Operational',
                        'credit' => 'Credit',
                        'market' => 'Market',
                        'liquidity' => 'Liquidity',
                        'compliance' => 'Compliance',
                        'reputational' => 'Reputational',
                        'strategic' => 'Strategic',
                        'cyber' => 'Cyber',
                        'aml' => 'AML & CFT',
                        'conduct' => 'Conduct',
                    ]),
                Tables\Filters\SelectFilter::make('residual_rating')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']),
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
            'index' => Pages\ListRisks::route('/'),
            'create' => Pages\CreateRisk::route('/create'),
            'edit' => Pages\EditRisk::route('/{record}/edit'),
            'view' => Pages\ViewRisk::route('/{record}'),
        ];
    }
}
