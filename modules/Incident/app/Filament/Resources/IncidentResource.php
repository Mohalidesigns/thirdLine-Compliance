<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Incident\Filament\Resources\IncidentResource\Pages;
use Modules\Incident\Filament\Resources\IncidentResource\RelationManagers;
use Modules\Incident\Models\Incident;
use Modules\Incident\Services\IncidentService;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static string|\UnitEnum|null $navigationGroup = 'Incidents';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Incident Details')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->options([
                        'cyber' => 'Cyber',
                        'data_breach' => 'Data Breach',
                        'conduct' => 'Conduct',
                        'financial_crime' => 'Financial Crime',
                        'operational' => 'Operational',
                        'customer_protection' => 'Customer Protection',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\Select::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ])
                    ->required(),
                Forms\Components\Select::make('basel_category')
                    ->label('Basel Category')
                    ->options([
                        'internal_fraud' => 'Internal Fraud',
                        'external_fraud' => 'External Fraud',
                        'employment_practices' => 'Employment Practices & Workplace Safety',
                        'clients_products_business' => 'Clients, Products & Business Practices',
                        'damage_physical_assets' => 'Damage to Physical Assets',
                        'business_disruption' => 'Business Disruption & System Failures',
                        'execution_delivery_process' => 'Execution, Delivery & Process Management',
                        'other' => 'Other',
                    ])
                    ->nullable(),
                Forms\Components\DateTimePicker::make('detected_at')
                    ->label('Detected At')
                    ->required(),
                Forms\Components\DateTimePicker::make('occurred_at')
                    ->label('Occurred At')
                    ->nullable(),
                Forms\Components\Select::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Flags')->schema([
                Forms\Components\Toggle::make('is_data_breach')->label('Data Breach'),
                Forms\Components\Toggle::make('is_cyber_incident')->label('Cyber Incident'),
                Forms\Components\Toggle::make('affects_customers')->label('Affects Customers'),
                Forms\Components\TextInput::make('affected_customer_count')
                    ->label('Affected Customer Count')
                    ->numeric()
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Financial Impact')->schema([
                Forms\Components\TextInput::make('financial_impact')
                    ->numeric()
                    ->nullable(),
                Forms\Components\TextInput::make('currency')
                    ->maxLength(3)
                    ->default('NGN')
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Root Cause & Lessons Learned')->schema([
                Forms\Components\Textarea::make('root_cause')->rows(3)->columnSpanFull(),
                Forms\Components\Textarea::make('lessons_learned')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cyber' => 'Cyber',
                        'data_breach' => 'Data Breach',
                        'conduct' => 'Conduct',
                        'financial_crime' => 'Financial Crime',
                        'operational' => 'Operational',
                        'customer_protection' => 'Customer Protection',
                        default => ucfirst((string) $state),
                    }),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Incident $record) => $record->statusLabel())
                    ->color(fn (Incident $record) => $record->statusColor()),
                Tables\Columns\IconColumn::make('is_data_breach')
                    ->label('Breach')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_cyber_incident')
                    ->label('Cyber')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('detected_at')
                    ->label('Detected')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('days_open')
                    ->label('Days Open')
                    ->getStateUsing(fn (Incident $record) => $record->daysOpen())
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options(['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low']),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'cyber' => 'Cyber',
                        'data_breach' => 'Data Breach',
                        'conduct' => 'Conduct',
                        'financial_crime' => 'Financial Crime',
                        'operational' => 'Operational',
                        'customer_protection' => 'Customer Protection',
                        'other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'detected' => 'Detected',
                        'triaged' => 'Triaged',
                        'investigating' => 'Investigating',
                        'remediation' => 'Remediation',
                        'resolved' => 'Resolved',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('basel_category')
                    ->label('Basel Category')
                    ->options([
                        'internal_fraud' => 'Internal Fraud',
                        'external_fraud' => 'External Fraud',
                        'employment_practices' => 'Employment Practices',
                        'clients_products_business' => 'Clients/Products',
                        'damage_physical_assets' => 'Physical Assets',
                        'business_disruption' => 'Business Disruption',
                        'execution_delivery_process' => 'Execution/Delivery',
                        'other' => 'Other',
                    ]),
                Tables\Filters\TernaryFilter::make('is_data_breach')
                    ->label('Data Breach'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('triage')
                    ->label('Triage')
                    ->icon('heroicon-o-arrow-right')
                    ->visible(fn (Incident $record) => $record->status::$name === 'detected')
                    ->action(function (Incident $record): void {
                        app(IncidentService::class)->transition($record, 'triaged', auth()->id());
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('investigate')
                    ->label('Investigate')
                    ->icon('heroicon-o-magnifying-glass')
                    ->visible(fn (Incident $record) => $record->status::$name === 'triaged')
                    ->action(function (Incident $record): void {
                        app(IncidentService::class)->transition($record, 'investigating', auth()->id());
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('remediate')
                    ->label('Remediation')
                    ->icon('heroicon-o-wrench')
                    ->visible(fn (Incident $record) => $record->status::$name === 'investigating')
                    ->action(function (Incident $record): void {
                        app(IncidentService::class)->transition($record, 'remediation', auth()->id());
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Incident $record) => $record->status::$name === 'remediation')
                    ->action(function (Incident $record): void {
                        app(IncidentService::class)->transition($record, 'resolved', auth()->id());
                    })
                    ->requiresConfirmation(),
            ])
            ->defaultSort('detected_at', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\ActionsRelationManager::class,
            RelationManagers\NotificationsRelationManager::class,
            RelationManagers\EvidenceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
            'view' => Pages\ViewIncident::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('incidents.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('incidents.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('incidents.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('incidents.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('incidents.delete') ?? false;
    }
}
