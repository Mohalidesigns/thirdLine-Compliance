<?php

declare(strict_types=1);

namespace Modules\Returns\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Returns\Filament\Resources\ReturnDefinitionResource\Pages;
use Modules\Returns\Models\ReturnDefinition;

class ReturnDefinitionResource extends Resource
{
    protected static ?string $model = ReturnDefinition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Returns';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Identity')->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->nullable()
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Legal References')->schema([
                Forms\Components\TextInput::make('acts')
                    ->label('Acts / Legislation')
                    ->maxLength(500)
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('legal_basis')
                    ->label('Legal Basis (e.g. s. 9(4))')
                    ->maxLength(200)
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Classification')->schema([
                Forms\Components\Select::make('regulator')
                    ->options(ReturnDefinition::regulatorLabels())
                    ->required(),
                Forms\Components\Select::make('submission_channel')
                    ->label('Submission Channel')
                    ->options([
                        'portal' => 'Portal',
                        'api' => 'API',
                        'sftp' => 'SFTP',
                        'email' => 'Email',
                        'goaml_xml' => 'goAML XML',
                        'firs_tax_pro_max' => 'FIRS TaxPro Max',
                        'cbn_efass' => 'CBN eFASS',
                        'manual' => 'Manual',
                    ])
                    ->required(),
                Forms\Components\Select::make('file_format')
                    ->label('File Format')
                    ->options([
                        'xml' => 'XML',
                        'xlsx' => 'XLSX',
                        'json' => 'JSON',
                        'pdf' => 'PDF',
                        'csv' => 'CSV',
                        'txt' => 'TXT',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\Select::make('frequency')
                    ->options(ReturnDefinition::frequencyLabels())
                    ->required(),
                Forms\Components\TextInput::make('responsible_unit')
                    ->label('Responsible Unit')
                    ->maxLength(200)
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Configuration')->schema([
                Forms\Components\Toggle::make('evidence_required')
                    ->label('Evidence Required')
                    ->default(true),
                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true),
            ])->columns(2),
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
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('regulator')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReturnDefinition::regulatorLabels()[$state] ?? strtoupper($state))
                    ->color(fn ($state) => match ($state) {
                        'cbn' => 'info',
                        'nfiu' => 'warning',
                        'ndic' => 'gray',
                        'firs' => 'danger',
                        'sec' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('frequency')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReturnDefinition::frequencyLabels()[$state] ?? ucfirst($state)),
                Tables\Columns\TextColumn::make('submission_channel')
                    ->label('Channel')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('evidence_required')
                    ->label('Evidence')
                    ->boolean(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('runs_count')
                    ->label('Runs')
                    ->counts('runs')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('regulator')
                    ->options(ReturnDefinition::regulatorLabels()),
                Tables\Filters\SelectFilter::make('frequency')
                    ->options(ReturnDefinition::frequencyLabels()),
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (ReturnDefinition $record) => $record->active)
                    ->requiresConfirmation()
                    ->action(fn (ReturnDefinition $record) => $record->update(['active' => false])),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (ReturnDefinition $record) => ! $record->active)
                    ->requiresConfirmation()
                    ->action(fn (ReturnDefinition $record) => $record->update(['active' => true])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnDefinitions::route('/'),
            'create' => Pages\CreateReturnDefinition::route('/create'),
            'edit' => Pages\EditReturnDefinition::route('/{record}/edit'),
            'view' => Pages\ViewReturnDefinition::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('returns.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('returns.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('returns.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('returns.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('returns.manage') ?? false;
    }
}
