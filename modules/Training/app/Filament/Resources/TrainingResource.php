<?php

declare(strict_types=1);

namespace Modules\Training\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Training\Filament\Resources\TrainingResource\Pages;
use Modules\Training\Models\Training;
use Modules\Training\Services\TrainingService;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Training';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Section::make('Training Details')->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(300)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Select::make('category')
                    ->options(self::categoryOptions())
                    ->required(),
                Forms\Components\Toggle::make('is_mandatory')
                    ->label('Mandatory')
                    ->default(false),
                Forms\Components\TextInput::make('sla_days')
                    ->label('SLA Days')
                    ->numeric()
                    ->default(30)
                    ->minValue(1)
                    ->required(),
                Forms\Components\Select::make('source')
                    ->options([
                        'native' => 'Native',
                        'scorm' => 'SCORM',
                        'xapi' => 'xAPI',
                        'external' => 'External',
                    ])
                    ->default('native')
                    ->required(),
                Forms\Components\TextInput::make('source_url')
                    ->label('Source URL')
                    ->url()
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\TagsInput::make('target_roles')
                    ->label('Target Roles (empty = all users)')
                    ->nullable()
                    ->columnSpanFull(),
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::categoryOptions()[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_mandatory')
                    ->label('Mandatory')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sla_days')
                    ->label('SLA Days')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'native' => 'success',
                        'scorm' => 'info',
                        'xapi' => 'warning',
                        'external' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('enrollments_count')
                    ->label('Enrollments')
                    ->counts('enrollments')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(self::categoryOptions()),
                Tables\Filters\TernaryFilter::make('is_mandatory')
                    ->label('Mandatory'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('enroll_eligible_users')
                    ->label('Enroll Eligible Users')
                    ->icon('heroicon-o-user-plus')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $service = app(TrainingService::class);
                        $totalCreated = 0;

                        foreach ($records as $training) {
                            $targetRoles = $training->target_roles ?? [];

                            if (empty($targetRoles)) {
                                $users = User::all();
                            } else {
                                $users = User::role($targetRoles)->get();
                            }

                            $totalCreated += $service->bulkEnroll($training, $users);
                        }

                        Notification::make()
                            ->title("Enrolled {$totalCreated} user(s) across selected trainings.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('code', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainings::route('/'),
            'create' => Pages\CreateTraining::route('/create'),
            'edit' => Pages\EditTraining::route('/{record}/edit'),
            'view' => Pages\ViewTraining::route('/{record}'),
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

    /** @return array<string, string> */
    private static function categoryOptions(): array
    {
        return [
            'aml' => 'AML/CFT',
            'sanctions' => 'Sanctions',
            'ndpa' => 'NDPA/Privacy',
            'abac' => 'ABAC',
            'conduct' => 'Conduct',
            'ethics' => 'Ethics',
            'esg' => 'ESG',
            'cyber' => 'Cyber',
            'customer_protection' => 'Customer Protection',
            'general' => 'General',
        ];
    }
}
