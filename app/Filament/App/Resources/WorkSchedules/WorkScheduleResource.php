<?php

namespace App\Filament\App\Resources\WorkSchedules;

use App\Filament\App\Resources\WorkSchedules\Pages\ManageWorkSchedules;
use App\Filament\App\Resources\WorkSchedules\Schemas\FixedScheduleForm;
use App\Filament\App\Resources\WorkSchedules\Schemas\FlexibleScheduleForm;
use App\Models\WorkSchedule;
use App\Traits\ScopesTenantResource;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\utils\Regex;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class WorkScheduleResource extends Resource
{
    use ScopesTenantResource;

    protected static ?string $model = WorkSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static bool $shouldRegisterGlobalSearch = false;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('work_schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('work_schedule', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('schedule')
                ->columnSpanFull()
                ->tabs([
                    Tab::make(traduct('sections.work_schedule.tab_general'))
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(150)
                                ->regex(Regex::PHRASE)
                                ->label(traduct('fields.schedule_name')),
                            Textarea::make('description')
                                ->default(null)
                                ->label(traduct('fields.description')),
                        ]),

                    Tab::make(traduct('sections.work_schedule.tab_schedule'))
                        ->schema([
                            Select::make('schedule_type')
                                ->options([
                                    'fixed'    => traduct('fields.schedule_fixed'),
                                    'flexible' => traduct('fields.schedule_flexible'),
                                    'rotating' => traduct('fields.schedule_rotating'),
                                ])
                                ->default('fixed')
                                ->required()
                                ->live()
                                ->label(traduct('fields.schedule_type')),

                            FixedScheduleForm::make(),

                            FlexibleScheduleForm::make(),

                            // Más adelante:
                            // RotatingScheduleForm::make(),
                        ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Horario')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct('sections.general'))
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->schema([
                                TextEntry::make('name')
                                    ->label(traduct('fields.name')),
                                TextEntry::make('schedule_type')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'fixed'    => traduct('fields.schedule_fixed'),
                                        'flexible' => traduct('fields.schedule_flexible'),
                                        'rotating' => traduct('fields.schedule_rotating'),
                                        default    => $state,
                                    })
                                    ->color(fn(string $state): string => match ($state) {
                                        'fixed'    => 'indigo',
                                        'flexible' => 'teal',
                                        'rotating' => 'success',
                                        default    => 'gray',
                                    })
                                    ->label(traduct('fields.schedule_type')),
                                TextEntry::make('description')
                                    ->placeholder(__('empty.description'))
                                    ->label(traduct('fields.description'))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(traduct('sections.work_schedule.tab_schedule'))
                            ->icon(Heroicon::OutlinedClock)
                            ->columns(1)
                            ->schema([
                                RepeatableEntry::make('days')
                                    ->label(traduct('sections.work_schedule.tab_schedule'))
                                    ->visible(fn(WorkSchedule $record): bool => $record->schedule_type === 'fixed')
                                    ->placeholder(__('empty.generic'))
                                    ->columns(4)
                                    ->schema([
                                        TextEntry::make('day_of_week')
                                            ->label(traduct('fields.day_of_week'))
                                            ->badge()
                                            ->formatStateUsing(fn(int|string|null $state): string => match ((int) $state) {
                                                1 => traductShort('status.day_of_week.monday'),
                                                2 => traductShort('status.day_of_week.tuesday'),
                                                3 => traductShort('status.day_of_week.wednesday'),
                                                4 => traductShort('status.day_of_week.thursday'),
                                                5 => traductShort('status.day_of_week.friday'),
                                                6 => traductShort('status.day_of_week.saturday'),
                                                7 => traductShort('status.day_of_week.sunday'),
                                                default => __('empty.generic'),
                                            }),
                                        IconEntry::make('is_working_day')
                                            ->label(traduct('fields.is_working_day'))
                                            ->boolean(),
                                        TextEntry::make('check_in_time')
                                            ->label(traduct('fields.check_in_time'))
                                            ->time('g:i A')
                                            ->placeholder(__('empty.time')),
                                        TextEntry::make('check_out_time')
                                            ->label(traduct('fields.check_out_time'))
                                            ->time('g:i A')
                                            ->placeholder(__('empty.time')),
                                        TextEntry::make('break_start_time')
                                            ->label(traduct('fields.break_start_time'))
                                            ->time('g:i A')
                                            ->placeholder(__('empty.time')),
                                        TextEntry::make('break_end_time')
                                            ->label(traduct('fields.break_end_time'))
                                            ->time('g:i A')
                                            ->placeholder(__('empty.time')),
                                        TextEntry::make('check_in_tolerance_minutes')
                                            ->label(traduct('fields.check_in_tolerance'))
                                            ->numeric()
                                            ->suffix(' min'),
                                        TextEntry::make('check_out_tolerance_minutes')
                                            ->label(traduct('fields.check_out_tolerance'))
                                            ->numeric()
                                            ->suffix(' min'),
                                    ]),

                                TextEntry::make('schedule_type_notice')
                                    ->label(traduct('fields.schedule_type'))
                                    ->visible(fn(WorkSchedule $record): bool => $record->schedule_type !== 'fixed')
                                    ->state(fn(WorkSchedule $record): string => match ($record->schedule_type) {
                                        'flexible' => traduct('fields.schedule_flexible'),
                                        'rotating' => traduct('fields.schedule_rotating'),
                                        default    => (string) $record->schedule_type,
                                    }),
                            ]),

                        Tab::make(traduct('sections.audit'))
                            ->icon(Heroicon::OutlinedClock)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.created_at')),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.updated_at')),
                                TextEntry::make('deleted_at')
                                    ->dateTime()
                                    ->visible(fn(WorkSchedule $record): bool => $record->trashed())
                                    ->label(traduct('fields.deleted_at')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label(traduct('fields.schedule_name')),
                TextColumn::make('schedule_type')
                    ->label(traduct('fields.schedule_type'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'fixed' => traduct('fields.schedule_fixed'),
                        'flexible' => traduct('fields.schedule_flexible'),
                        'rotating' => traduct('fields.schedule_rotating'),
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'fixed' => 'indigo',
                        'flexible' => 'teal',
                        'rotating' => 'success',
                        default => 'gray',
                    })
                    ->label(traduct('fields.schedule_type')),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct('fields.created_at')),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct('fields.updated_at')),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct('fields.deleted_at')),
            ])
            ->stackedOnMobile()
            ->recordClasses(fn(WorkSchedule $record) => $record->trashed() ? 'is-trashed' : null)
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->label(__('actions.soft_delete'))
                    ->modalHeading(__('modals.trash.heading'))
                    ->modalDescription(__('modals.trash.description'))
                    ->icon('heroicon-m-archive-box'),

                RestoreAction::make()
                    ->label(__('actions.restore'))
                    ->modalHeading(__('modals.restore.heading'))
                    ->modalDescription(__('modals.restore.description'))
                    ->color('info'),

                ForceDeleteAction::make()
                    ->label(__('actions.delete'))
                    ->modalHeading(__('modals.force_delete.heading'))
                    ->modalDescription(__('modals.force_delete.description'))
                    ->color('danger')
                    ->before(function (ForceDeleteAction $action, Model $record) {
                        if (
                            $record->days()->exists() ||
                            $record->employeeSchedules()->exists()
                        ) {
                            Notification::make()
                                ->danger()
                                ->title(__('messages.cannot_delete_title'))
                                ->body(__('messages.restrict_delete_body', [
                                    'entity' => traductModel('work_schedule'),
                                    'name' => $record->name,
                                ]))
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading(__('modals.bulk.trash.heading'))
                        ->modalDescription(__('modals.bulk.trash.description')),

                    RestoreBulkAction::make()
                        ->modalHeading(__('modals.bulk.restore.heading'))
                        ->modalDescription(__('modals.bulk.restore.description')),

                    ForceDeleteBulkAction::make()
                        ->modalHeading(__('modals.bulk.force_delete.heading'))
                        ->modalDescription(__('modals.bulk.force_delete.description'))
                        ->before(function (ForceDeleteBulkAction $action, Collection $records) {
                            foreach ($records as $record) {
                                if (
                                    $record->days()->exists() ||
                                    $record->employeeSchedules()->exists()
                                ) {
                                    Notification::make()
                                        ->danger()
                                        ->title(__('messages.cannot_delete_title'))
                                        ->body(__('messages.restrict_delete_body', [
                                            'entity' => traductModel('work_schedule'),
                                            'name' => $record->name,
                                        ]))
                                        ->persistent()
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWorkSchedules::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
