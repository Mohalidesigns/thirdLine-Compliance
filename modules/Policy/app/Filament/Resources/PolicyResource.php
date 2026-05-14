<?php

declare(strict_types=1);

namespace Modules\Policy\Filament\Resources;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Policy\Filament\Resources\PolicyResource\Pages;
use Modules\Policy\Models\Policy;
use Modules\Policy\Services\PolicyService;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Policy';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Policy Details')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->options([
                        'aml' => 'AML & CFT',
                        'data_protection' => 'Data Protection',
                        'risk' => 'Risk Management',
                        'conduct' => 'Conduct',
                        'cyber' => 'Cybersecurity',
                        'governance' => 'Governance',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('owner_team')
                    ->label('Owner Team')
                    ->required()
                    ->maxLength(200),
                Forms\Components\Textarea::make('summary')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('body')
                    ->label('Policy Content')
                    ->rows(10)
                    ->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Dates')->schema([
                Forms\Components\DatePicker::make('effective_date')
                    ->label('Effective Date'),
                Forms\Components\DatePicker::make('next_review_date')
                    ->label('Next Review Date'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('category')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'aml' => 'AML & CFT',
                        'data_protection' => 'Data Protection',
                        'risk' => 'Risk Management',
                        'conduct' => 'Conduct',
                        'cyber' => 'Cybersecurity',
                        'governance' => 'Governance',
                        default => ucfirst((string) $state),
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'gray',
                        'in_review' => 'info',
                        'approved' => 'warning',
                        'published' => 'primary',
                        'in_force' => 'success',
                        'under_review' => 'warning',
                        'superseded' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Draft',
                        'in_review' => 'In Review',
                        'approved' => 'Approved',
                        'published' => 'Published',
                        'in_force' => 'In Force',
                        'under_review' => 'Under Review',
                        'superseded' => 'Superseded',
                        default => ucfirst((string) $state),
                    }),
                Tables\Columns\TextColumn::make('owner_team')
                    ->label('Owner')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('v')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'draft' => 'Draft',
                        'in_review' => 'In Review',
                        'approved' => 'Approved',
                        'published' => 'Published',
                        'in_force' => 'In Force',
                        'under_review' => 'Under Review',
                        'superseded' => 'Superseded',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'aml' => 'AML & CFT',
                        'data_protection' => 'Data Protection',
                        'risk' => 'Risk Management',
                        'conduct' => 'Conduct',
                        'cyber' => 'Cybersecurity',
                        'governance' => 'Governance',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Policy $record) => $record->state::$name === 'draft'),
                Tables\Actions\Action::make('submit_review')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Policy $record) => $record->state::$name === 'draft')
                    ->action(function (Policy $record): void {
                        app(PolicyService::class)->transition($record, 'in_review', null, auth()->id());
                        Notification::make()->title('Policy submitted for review')->success()->send();
                    }),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Policy $record) => $record->state::$name === 'in_review')
                    ->action(function (Policy $record): void {
                        app(PolicyService::class)->transition($record, 'approved', null, auth()->id());
                        Notification::make()->title('Policy approved')->success()->send();
                    }),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->visible(fn (Policy $record) => $record->state::$name === 'approved')
                    ->action(function (Policy $record): void {
                        app(PolicyService::class)->transition($record, 'published', null, auth()->id());
                        Notification::make()->title('Policy published — PDF render queued')->success()->send();
                    }),
                Tables\Actions\Action::make('request_changes')
                    ->label('Request Changes')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Policy $record) => $record->state::$name === 'in_review')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Note (required)')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Policy $record, array $data): void {
                        app(PolicyService::class)->transition($record, 'draft', $data['note'], auth()->id());
                        Notification::make()->title('Changes requested — policy returned to draft')->warning()->send();
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
            'view' => Pages\ViewPolicy::route('/{record}'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record->state::$name === 'draft';
    }
}
