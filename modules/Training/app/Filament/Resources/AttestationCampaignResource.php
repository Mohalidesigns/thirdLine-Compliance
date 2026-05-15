<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources;

use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Training\Filament\Resources\AttestationCampaignResource\Pages;
use Modules\Training\Filament\Resources\AttestationCampaignResource\RelationManagers\RecordsRelationManager;
use Modules\Training\Models\AttestationCampaign;

class AttestationCampaignResource extends Resource
{
    protected static ?string $model = AttestationCampaign::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Training';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Campaign Details')->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('body')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('starts_at')->required(),
                Forms\Components\DateTimePicker::make('ends_at')->required(),
                Forms\Components\TagsInput::make('mandatory_for_roles')
                    ->label('Mandatory For Roles')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'closed' => 'Closed',
                    ])
                    ->default('draft')
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'closed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('starts_at')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('records_count')
                    ->label('Signatures')
                    ->counts('records')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'closed' => 'Closed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (AttestationCampaign $record): void {
                        if ($record->status !== 'draft') {
                            Notification::make()->warning()->title('Campaign is not a draft.')->send();

                            return;
                        }
                        $record->update(['status' => 'active']);
                        Notification::make()->success()->title('Campaign published.')->send();
                    })
                    ->visible(fn (AttestationCampaign $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (AttestationCampaign $record): void {
                        if ($record->status !== 'active') {
                            Notification::make()->warning()->title('Campaign is not active.')->send();

                            return;
                        }
                        $record->update(['status' => 'closed']);
                        Notification::make()->success()->title('Campaign closed.')->send();
                    })
                    ->visible(fn (AttestationCampaign $record) => $record->status === 'active'),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [
            RecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttestationCampaigns::route('/'),
            'create' => Pages\CreateAttestationCampaign::route('/create'),
            'edit' => Pages\EditAttestationCampaign::route('/{record}/edit'),
            'view' => Pages\ViewAttestationCampaign::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('attestations.manage') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('attestations.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('attestations.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('attestations.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('attestations.manage') ?? false;
    }
}
