<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources;

use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Training\Filament\Resources\TrainingEnrollmentResource\Pages;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\TrainingService;

class TrainingEnrollmentResource extends Resource
{
    protected static ?string $model = TrainingEnrollment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Training';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Enrollment Details')->schema([
                Forms\Components\Select::make('training_id')
                    ->relationship('training', 'title')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                        'exempted' => 'Exempted',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('enrolled_at')->required(),
                Forms\Components\DateTimePicker::make('due_at')->required(),
                Forms\Components\DateTimePicker::make('completed_at')->nullable(),
                Forms\Components\TextInput::make('score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->nullable(),
                Forms\Components\Textarea::make('exemption_reason')->nullable()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('training.title')->label('Training')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'info',
                        'enrolled' => 'gray',
                        'overdue' => 'danger',
                        'exempted' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('due_at')->dateTime('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('completed_at')->dateTime('d M Y')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('score')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('training')
                    ->relationship('training', 'title'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'overdue' => 'Overdue',
                        'exempted' => 'Exempted',
                    ]),
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('grant_exemption')
                    ->label('Grant Exemption')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Exemption Reason')
                            ->required()
                            ->minLength(10),
                    ])
                    ->action(function (TrainingEnrollment $record, array $data): void {
                        $service = app(TrainingService::class);
                        $service->grantExemption($record, $data['reason'], auth()->id());
                    })
                    ->visible(fn (TrainingEnrollment $record) => ! in_array($record->status, ['exempted', 'completed'], true)),
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('score')
                            ->label('Score (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                    ])
                    ->action(function (TrainingEnrollment $record, array $data): void {
                        $service = app(TrainingService::class);
                        $service->markCompleted($record, isset($data['score']) ? (int) $data['score'] : null, auth()->id());
                    })
                    ->visible(fn (TrainingEnrollment $record) => ! in_array($record->status, ['completed', 'exempted'], true)),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('due_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingEnrollments::route('/'),
            'view' => Pages\ViewTrainingEnrollment::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('training.view') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->can('training.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('training.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('training.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('training.manage') ?? false;
    }
}
