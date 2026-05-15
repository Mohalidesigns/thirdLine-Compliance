<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'actions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
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
            Forms\Components\Textarea::make('evidence_notes')->rows(2)->columnSpanFull()->label('Evidence Notes'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('title')->limit(40),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner'),
                Tables\Columns\TextColumn::make('due_at')->date('d M Y')->label('Due'),
                Tables\Columns\TextColumn::make('completed_at')->dateTime('d M Y')->label('Completed')->placeholder('—'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check')
                    ->form([
                        Forms\Components\Textarea::make('evidence_notes')->label('Evidence Notes')->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'completed_at' => now(),
                            'completed_by' => auth()->id(),
                            'evidence_notes' => $data['evidence_notes'] ?? $record->evidence_notes,
                        ]);
                    })
                    ->visible(fn ($record) => $record->completed_at === null),
            ]);
    }
}
