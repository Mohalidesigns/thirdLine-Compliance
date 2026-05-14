<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class RecentAuditEventsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Audit Events')
            ->query(
                fn () => DB::table('audit_events')
                    ->orderByDesc('recorded_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state) => class_basename((string) $state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('actor_id')
                    ->label('Actor ID')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recorded_at')
                    ->label('Recorded At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->paginated(false);
    }
}
