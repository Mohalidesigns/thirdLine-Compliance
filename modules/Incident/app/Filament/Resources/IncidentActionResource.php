<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Incident\Filament\Resources\IncidentActionResource\Pages;
use Modules\Incident\Models\IncidentAction;

class IncidentActionResource extends Resource
{
    protected static ?string $model = IncidentAction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Incidents';

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'CAPA Actions';

    protected static ?string $pluralLabel = 'CAPA Actions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Action Details')->schema([
                Forms\Components\Select::make('incident_id')
                    ->label('Incident')
                    ->relationship('incident', 'code')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options(['corrective' => 'Corrective', 'preventive' => 'Preventive'])
                    ->required(),
                Forms\Components\TextInput::make('title')->required()->maxLength(500)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                Forms\Components\Select::make('owner_user_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\DatePicker::make('due_at')->label('Due Date')->required(),
                Forms\Components\Textarea::make('evidence_notes')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('incident.code')
                    ->label('Incident')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('title')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner')->toggleable(),
                Tables\Columns\TextColumn::make('due_at')->date('d M Y')->label('Due')->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime('d M Y')
                    ->label('Completed')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->getStateUsing(fn (IncidentAction $record) => $record->isOverdue())
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['corrective' => 'Corrective', 'preventive' => 'Preventive']),
                Tables\Filters\Filter::make('completed')
                    ->query(fn ($query) => $query->whereNotNull('completed_at'))
                    ->label('Completed Only'),
                Tables\Filters\Filter::make('pending')
                    ->query(fn ($query) => $query->whereNull('completed_at'))
                    ->label('Pending Only'),
                Tables\Filters\SelectFilter::make('owner_user_id')
                    ->label('Owner')
                    ->relationship('owner', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->form([
                        Forms\Components\Textarea::make('evidence_notes')
                            ->label('Evidence / Completion Notes')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'completed_at' => now(),
                            'completed_by' => auth()->id(),
                            'evidence_notes' => $data['evidence_notes'] ?? $record->evidence_notes,
                        ]);
                    })
                    ->visible(fn (IncidentAction $record) => $record->completed_at === null),
            ])
            ->defaultSort('due_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidentActions::route('/'),
            'create' => Pages\CreateIncidentAction::route('/create'),
            'edit' => Pages\EditIncidentAction::route('/{record}/edit'),
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
        return auth()->user()?->can('incidents.update') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('incidents.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('incidents.update') ?? false;
    }
}
