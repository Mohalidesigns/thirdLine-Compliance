<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources\AttestationCampaignResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = 'Signatures';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Signed By')->searchable(),
                Tables\Columns\TextColumn::make('signed_at')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('ip')->label('IP Address'),
            ])
            ->defaultSort('signed_at', 'desc');
    }
}
