<?php

namespace App\Filament\App\Resources\EmployeeSchedules;

use App\Filament\App\Resources\EmployeeSchedules\Pages\ManageEmployeeSchedules;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Traits\ScopesTenantResource;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeScheduleResource extends Resource
{

    use ScopesTenantResource;

    protected static ?string $model = EmployeeSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static bool $shouldRegisterGlobalSearch = false;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return traduct('navigation.attendance');
    }

    public static function getModelLabel(): string
    {
        return traductModel('employee_schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return traductModel('employee_schedule', plural: true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'id')
                    ->getOptionLabelFromRecordUsing(fn(Employee $record): string => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name']) // Permite buscar por cualquiera de los tres campos
                    ->preload()
                    ->required()
                    ->label(traductModel('employee')),
                Select::make('work_schedule_id')
                    ->relationship('workSchedule', 'name')
                    ->required()
                    ->label(traduct("fields.schedule_name")),
                DatePicker::make('start_date')
                    ->required()
                    ->label(traduct("fields.resolved_at"))
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->label(traduct("fields.start_date")),
                DatePicker::make('end_date')
                    ->label(traduct("fields.resolved_at"))
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->label(traduct("fields.end_date")),
                Toggle::make('status')
                    ->required()
                    ->label(traduct("fields.status")),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Asignacion')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(traduct('sections.general'))
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->schema([
                                TextEntry::make('employee_full_name')
                                    ->label(traduct('fields.employee'))
                                    ->placeholder(__('empty.employee'))
                                    ->state(fn($record) => trim(($record->employee?->first_name ?? '') . ' ' . ($record->employee?->last_name ?? '')) ?: null),
                                TextEntry::make('workSchedule.name')
                                    ->placeholder(__('empty.work_schedule'))
                                    ->label(traduct('fields.schedule_name')),
                                IconEntry::make('status')
                                    ->boolean()
                                    ->label(traduct('fields.status')),
                            ]),

                        Tab::make(traduct('sections.validity'))
                            ->icon(Heroicon::OutlinedClock)
                            ->schema([
                                TextEntry::make('start_date')
                                    ->date()
                                    ->placeholder(__('empty.date'))
                                    ->label(traduct('fields.start_date')),
                                TextEntry::make('end_date')
                                    ->date()
                                    ->placeholder(__('empty.end_date'))
                                    ->label(traduct('fields.end_date')),
                            ]),

                        Tab::make(traduct('sections.audit'))
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.created_at')),
                                TextEntry::make('updated_at')
                                    ->dateTime()
                                    ->placeholder(__('empty.generic'))
                                    ->label(traduct('fields.updated_at')),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('employee_full_name')
                    ->label(traduct('fields.employee'))
                    ->state(fn($record) => trim(($record->employee?->first_name ?? '') . ' ' . ($record->employee?->last_name ?? '')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employee', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('workSchedule.name')
                    ->searchable()
                    ->label(traduct("fields.schedule_name")),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label(traduct("fields.start_date")),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label(traduct("fields.end_date")),
                IconColumn::make('status')
                    ->boolean()
                    ->label(traduct("fields.status")),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.created_at")),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(traduct("fields.updated_at")),
            ])
            ->stackedOnMobile()
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->label(__('actions.delete'))
                    ->modalHeading(__('modals.force_delete.heading'))
                    ->modalDescription(__('modals.force_delete.description'))
                    ->color('danger'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalHeading(__('modals.bulk.force_delete.heading'))
                        ->modalDescription(__('modals.bulk.force_delete.description')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployeeSchedules::route('/'),
        ];
    }
}
