<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Training\Filament\Resources\CertificationResource\Pages;
use Modules\Training\Models\Certification;

class CertificationResource extends Resource
{
    protected static ?string $model = Certification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Training';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Certification Details')->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Certification Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('issuing_body')
                    ->required()
                    ->maxLength(200),
                Forms\Components\TextInput::make('certificate_no')
                    ->label('Certificate Number')
                    ->nullable(),
                Forms\Components\DatePicker::make('issued_at')->required(),
                Forms\Components\DatePicker::make('expires_at')->nullable(),
                Forms\Components\TextInput::make('evidence_path')
                    ->label('Evidence Path')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expiring' => 'Expiring',
                        'expired' => 'Expired',
                    ])
                    ->default('active')
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Certification')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('issuing_body')->label('Issuing Body')->limit(30)->toggleable(),
                Tables\Columns\TextColumn::make('certificate_no')->label('Cert No')->toggleable(),
                Tables\Columns\TextColumn::make('issued_at')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expiring' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('days_until_expiry')
                    ->label('Days Until Expiry')
                    ->getStateUsing(fn (Certification $record) => $record->daysUntilExpiry())
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expiring' => 'Expiring',
                        'expired' => 'Expired',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('expires_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertifications::route('/'),
            'create' => Pages\CreateCertification::route('/create'),
            'edit' => Pages\EditCertification::route('/{record}/edit'),
            'view' => Pages\ViewCertification::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('certifications.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('certifications.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('certifications.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('certifications.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('certifications.manage') ?? false;
    }
}
