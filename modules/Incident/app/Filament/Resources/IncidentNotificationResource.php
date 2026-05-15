<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Incident\Filament\Resources\IncidentNotificationResource\Pages;
use Modules\Incident\Models\IncidentNotification;

class IncidentNotificationResource extends Resource
{
    protected static ?string $model = IncidentNotification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'Incidents';

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'Regulator Notifications';

    protected static ?string $pluralLabel = 'Regulator Notifications';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Notification Details')->schema([
                Forms\Components\Select::make('incident_id')
                    ->label('Incident')
                    ->relationship('incident', 'code')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('regulator')
                    ->options([
                        'cbn_cyber' => 'CBN Cyber (4h)',
                        'cbn_general' => 'CBN General (24h)',
                        'ndpc' => 'NDPC (72h)',
                        'nfiu' => 'NFIU',
                        'sec' => 'SEC',
                        'others' => 'Others',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('deadline_at')->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'submitted' => 'Submitted',
                        'acknowledged' => 'Acknowledged',
                        'overdue' => 'Overdue',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\TextInput::make('notification_reference')->maxLength(200)->nullable(),
                Forms\Components\Textarea::make('notes')->rows(2)->nullable()->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('regulator')
                    ->formatStateUsing(fn (IncidentNotification $record) => $record->regulatorLabel()),
                Tables\Columns\TextColumn::make('deadline_at')
                    ->dateTime('d M Y H:i')
                    ->label('Deadline')
                    ->sortable(),
                Tables\Columns\TextColumn::make('notified_at')
                    ->dateTime('d M Y H:i')
                    ->label('Submitted At')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (IncidentNotification $record) => $record->statusColor()),
                Tables\Columns\TextColumn::make('notification_reference')->label('Reference')->placeholder('—')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('regulator')
                    ->options([
                        'cbn_cyber' => 'CBN Cyber',
                        'cbn_general' => 'CBN General',
                        'ndpc' => 'NDPC',
                        'nfiu' => 'NFIU',
                        'sec' => 'SEC',
                        'others' => 'Others',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'submitted' => 'Submitted',
                        'acknowledged' => 'Acknowledged',
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn ($query) => $query->where('status', 'overdue')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('record_submission')
                    ->label('Record Submission')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('reference')
                            ->label('Submission Reference')
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => 'submitted',
                            'notified_at' => now(),
                            'notification_reference' => $data['reference'],
                        ]);
                    })
                    ->visible(fn (IncidentNotification $record) => in_array($record->status, ['pending', 'overdue'])),
            ])
            ->defaultSort('deadline_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidentNotifications::route('/'),
            'edit' => Pages\EditIncidentNotification::route('/{record}/edit'),
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
        return auth()->user()?->can('incidents.notify') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('incidents.notify') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('incidents.update') ?? false;
    }
}
