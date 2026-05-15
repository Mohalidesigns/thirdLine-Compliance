<?php

declare(strict_types=1);

namespace Modules\Rcsa\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Rcsa\Filament\Resources\RiskAssessmentResource\Pages;
use Modules\Rcsa\Models\RiskAssessmentCycle;

class RiskAssessmentResource extends Resource
{
    protected static ?string $model = RiskAssessmentCycle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Risk';

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'Risk Assessment Cycle';

    protected static ?string $pluralLabel = 'Risk Assessment Cycles';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Cycle Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                Forms\Components\Select::make('lob')
                    ->label('Line of Business')
                    ->options([
                        'Retail' => 'Retail Banking',
                        'Corporate' => 'Corporate Banking',
                        'Treasury' => 'Treasury',
                        'Operations' => 'Operations',
                        'IT' => 'IT',
                        'Compliance' => 'Compliance',
                    ])
                    ->required(),
                Forms\Components\Select::make('methodology')
                    ->options([
                        '3x3' => '3×3 Matrix',
                        '5x5' => '5×5 Matrix',
                    ])
                    ->required()
                    ->default('3x3'),
                Forms\Components\TextInput::make('cycle_year')
                    ->label('Year')
                    ->numeric()
                    ->required()
                    ->default(now()->year),
                Forms\Components\Select::make('cycle_quarter')
                    ->label('Quarter')
                    ->options([1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'])
                    ->nullable(),
                Forms\Components\Select::make('lead_assessor_id')
                    ->label('Lead Assessor')
                    ->relationship('leadAssessor', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Textarea::make('summary')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Dates')->schema([
                Forms\Components\DatePicker::make('started_at')->label('Started At'),
                Forms\Components\DatePicker::make('sla_due_date')->label('SLA Due Date'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('name')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('lob')->badge(),
                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'planning' => 'gray',
                        'data_capture' => 'info',
                        'scoring' => 'warning',
                        'in_review' => 'primary',
                        'signed_off' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'planning' => 'Planning',
                        'data_capture' => 'Data Capture',
                        'scoring' => 'Scoring',
                        'in_review' => 'In Review',
                        'signed_off' => 'Signed Off',
                        'closed' => 'Closed',
                        default => ucfirst((string) $state),
                    }),
                Tables\Columns\TextColumn::make('cycle_year')->label('Year')->sortable(),
                Tables\Columns\TextColumn::make('methodology')->badge(),
                Tables\Columns\TextColumn::make('sla_due_date')->date('d M Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('risks_count')
                    ->label('Risks')
                    ->counts('risks')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'planning' => 'Planning',
                        'data_capture' => 'Data Capture',
                        'scoring' => 'Scoring',
                        'in_review' => 'In Review',
                        'signed_off' => 'Signed Off',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('lob')
                    ->options([
                        'Retail' => 'Retail Banking',
                        'Corporate' => 'Corporate Banking',
                        'Treasury' => 'Treasury',
                        'Operations' => 'Operations',
                        'IT' => 'IT',
                        'Compliance' => 'Compliance',
                    ]),
                Tables\Filters\SelectFilter::make('methodology')
                    ->options(['3x3' => '3×3', '5x5' => '5×5']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRiskAssessments::route('/'),
            'create' => Pages\CreateRiskAssessment::route('/create'),
            'edit' => Pages\EditRiskAssessment::route('/{record}/edit'),
            'view' => Pages\ViewRiskAssessment::route('/{record}'),
        ];
    }
}
