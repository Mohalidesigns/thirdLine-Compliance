<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Incident\Models\IncidentNotification;

class NotificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'notifications';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
            Forms\Components\TextInput::make('notification_reference')->maxLength(200)->nullable(),
            Forms\Components\Textarea::make('notes')->rows(2)->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('regulator')
                    ->formatStateUsing(fn (IncidentNotification $record) => $record->regulatorLabel()),
                Tables\Columns\TextColumn::make('deadline_at')->dateTime('d M Y H:i')->label('Deadline'),
                Tables\Columns\TextColumn::make('notified_at')->dateTime('d M Y H:i')->label('Submitted')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (IncidentNotification $record) => $record->statusColor()),
                Tables\Columns\TextColumn::make('notification_reference')->label('Reference')->placeholder('—'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\Action::make('record_submission')
                    ->label('Record Submission')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('reference')
                            ->label('Submission Reference')
                            ->required(),
                        Forms\Components\Textarea::make('notes')->rows(2)->nullable(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'status' => 'submitted',
                            'notified_at' => now(),
                            'notification_reference' => $data['reference'],
                            'notes' => $data['notes'] ?? $record->notes,
                        ]);
                    })
                    ->visible(fn ($record) => $record->status === 'pending' || $record->status === 'overdue'),
            ]);
    }
}
