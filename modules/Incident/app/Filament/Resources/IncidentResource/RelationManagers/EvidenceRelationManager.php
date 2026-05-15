<?php

declare(strict_types=1);

namespace Modules\Incident\Filament\Resources\IncidentResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Incident\Models\IncidentEvidence;

class EvidenceRelationManager extends RelationManager
{
    protected static string $relationship = 'evidence';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('type')
                ->options([
                    'document' => 'Document',
                    'screenshot' => 'Screenshot',
                    'email' => 'Email',
                    'log' => 'Log',
                    'witness_statement' => 'Witness Statement',
                    'other' => 'Other',
                ])
                ->required(),
            Forms\Components\TextInput::make('title')->required()->maxLength(500)->columnSpanFull(),
            Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull()->nullable(),
            Forms\Components\TextInput::make('file_path')->label('File Path')->nullable()->maxLength(1000)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (IncidentEvidence $record) => $record->typeLabel()),
                Tables\Columns\TextColumn::make('title')->limit(50),
                Tables\Columns\TextColumn::make('uploader.name')->label('Uploaded By'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->label('Uploaded At'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();
                        $data['created_at'] = now();
                        $data['tenant_id'] = 1; // BelongsToTenant will stamp this

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
