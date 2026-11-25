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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Closure;

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
        $getEmployee = function (Get $get): ?Employee {
            static $cachedId = null;
            static $cachedEmployee = null;

            $id = $get('employee_id');

            if (blank($id)) {
                return null;
            }

            if ($cachedId !== $id) {
                $cachedId = $id;
                $cachedEmployee = Employee::find($id);
            }

            return $cachedEmployee;
        };

        $validateOverlap = function (Get $get, ?Model $record): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                $employeeId = $get('employee_id');
                $startDate  = $get('start_date');
                $endDate    = $attribute === 'end_date' ? $value : $get('end_date');
                $isActive   = (bool) ($get('status') ?? true);

                if (blank($employeeId) || blank($startDate)) {
                    return;
                }

                if (filled($endDate) && $endDate < $startDate) {
                    $fail(traduct('validation.end_date_before_start_date') !== 'validation.end_date_before_start_date'
                        ? traduct('validation.end_date_before_start_date')
                        : 'La fecha de fin no puede ser anterior a la fecha de inicio.');

                    return;
                }

                // Si creas o editas con status = false (inactivo), no bloqueamos por solapamiento activo.
                // Si quieres que SIEMPRE bloquee aunque status sea false, quita este `if (! $isActive) return;`.
                if (! $isActive) {
                    return;
                }

                $overlapExists = EmployeeSchedule::query()
                    ->where('employee_id', $employeeId)
                    ->where('status', true)
                    ->when($record, fn($query) => $query->whereKeyNot($record->id))
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->when(
                            filled($endDate),
                            fn($sub) => $sub->where('start_date', '<=', $endDate)
                        );

                        $query->where(function ($sub) use ($startDate) {
                            $sub->whereNull('end_date')
                                ->orWhere('end_date', '>=', $startDate);
                        });
                    })
                    ->exists();

                if ($overlapExists) {
                    $fail('Este empleado ya tiene un horario activo asignado que se solapa con el rango de fechas indicado.');
                }
            };
        };

        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'id')
                    ->getOptionLabelFromRecordUsing(fn(Employee $record): string => "{$record->first_name} {$record->last_name} - {$record->employee_code}")
                    ->searchable(['first_name', 'last_name', 'employee_code'])
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('start_date', null);
                        $set('end_date', null);
                    })
                    ->label(traductModel('employee')),

                Select::make('work_schedule_id')
                    ->relationship('workSchedule', 'name')
                    ->required()
                    ->label(traduct('fields.schedule_name')),

                DatePicker::make('start_date')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->live()
                    ->disabled(fn(Get $get): bool => blank($get('employee_id')))
                    ->minDate(fn(Get $get) => $getEmployee($get)?->hire_date)
                    ->maxDate(fn(Get $get) => $get('end_date') ?: $getEmployee($get)?->termination_date)
                    ->afterStateUpdated(function (Set $set): void {
                        $set('end_date', null);
                    })
                    ->rule(fn(Get $get, ?Model $record): Closure => $validateOverlap($get, $record))
                    ->label(traduct('fields.start_date')),

                DatePicker::make('end_date')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->live()
                    ->disabled(fn(Get $get): bool => blank($get('employee_id')) || blank($get('start_date')))
                    ->minDate(fn(Get $get) => $get('start_date'))
                    ->maxDate(fn(Get $get) => $getEmployee($get)?->termination_date)
                    ->rule('after_or_equal:start_date')
                    ->rule(fn(Get $get, ?Model $record): Closure => $validateOverlap($get, $record))
                    ->label(traduct('fields.end_date')),

                Toggle::make('status')
                    ->required()
                    ->default(true)
                    ->live()
                    ->label(traduct('fields.status')),
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
