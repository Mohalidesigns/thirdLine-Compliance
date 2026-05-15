<?php

declare(strict_types=1);

namespace Modules\Controls\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Controls\Filament\Resources\IssueResource\Pages;
use Modules\Controls\Models\Issue;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Controls';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Issue Details')->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(300)->columnSpanFull(),
                Forms\Components\Select::make('source_type')
                    ->options([
                        'control_test' => 'Control Test',
                        'manual' => 'Manual',
                        'ccm_rule' => 'CCM Rule',
                        'audit_finding' => 'Audit Finding',
                    ])
                    ->required(),
                Forms\Components\Select::make('severity')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'dismissed' => 'Dismissed'])
                    ->default('open')
                    ->required(),
                Forms\Components\TextInput::make('owner_team')->maxLength(200),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                Forms\Components\Textarea::make('resolution_notes')->rows(2)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')->badge()->color('gray')->searchable(),
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('source_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'control_test' => 'Control Test',
                        'ccm_rule' => 'CCM Rule',
                        'audit_finding' => 'Audit Finding',
                        default => ucfirst((string) $state),
                    }),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'open' => 'danger',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        'dismissed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_date')->date('d M Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('owner_team')->label('Owner')->toggleable()->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed']),
                Tables\Filters\SelectFilter::make('severity')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical']),
                Tables\Filters\SelectFilter::make('source_type')
                    ->options(['control_test' => 'Control Test', 'manual' => 'Manual', 'ccm_rule' => 'CCM Rule', 'audit_finding' => 'Audit Finding']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
            'view' => Pages\ViewIssue::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('issues.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('issues.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('issues.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('issues.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('issues.delete') ?? false;
    }
}
