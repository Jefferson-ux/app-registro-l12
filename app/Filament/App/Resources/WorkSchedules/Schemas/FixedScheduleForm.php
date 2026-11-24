<?php

namespace App\Filament\App\Resources\WorkSchedules\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Harvirsidhu\FilamentTimepicker\SmartTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class FixedScheduleForm
{
    /**
     * Todo lo que se muestra cuando schedule_type = 'fixed'.
     */
    public static function make(): Group
    {
        return Group::make()
            ->visible(fn(Get $get) => $get('schedule_type') === 'fixed')
            ->columnSpanFull()
            ->schema([
                static::daysTable(),
                static::tolerances(),
            ]);
    }

    /**
     * Tabla de los 7 días, dentro de un contenedor con scroll horizontal.
     */
    protected static function daysTable(): Grid
    {
        $dayNames = static::dayNames();

        $timeFields = ['check_in_time', 'break_start_time', 'break_end_time', 'check_out_time'];

        return Grid::make(1)
            ->extraAttributes(['style' => 'overflow-x: auto; max-width: 100%;'])
            ->schema([
                Repeater::make('days')
                    ->relationship('days')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->label(traductModel('work_schedule_day', plural: true))
                    ->extraAttributes(['style' => 'min-width: 760px;'])
                    ->default(fn() => collect(range(1, 7))->map(fn($d) => [
                        'day_of_week'                 => $d,
                        'is_working_day'              => $d <= 5,
                        'check_in_time'               => $d <= 5 ? '08:00:00' : null,
                        'break_start_time'            => null,
                        'break_end_time'              => null,
                        'check_out_time'              => $d <= 5 ? '17:00:00' : null,
                        'check_in_tolerance_minutes'  => 0,
                        'check_out_tolerance_minutes' => 0,
                        'auto_close_after_minutes'    => 10,
                    ])->all())
                    ->table([
                        TableColumn::make('')->width('130px'),
                        TableColumn::make(traduct('sections.work_schedule.working_day'))->width('100px'),
                        TableColumn::make(traduct('sections.work_schedule.check_in'))->width('120px'),
                        TableColumn::make(traduct('sections.work_schedule.break_start_short'))->width('120px'),
                        TableColumn::make(traduct('sections.work_schedule.break_end_short'))->width('120px'),
                        TableColumn::make(traduct('sections.work_schedule.check_out'))->width('120px'),
                    ])
                    ->schema([
                        // Nombre del día como texto; el valor real va en el Hidden
                        Hidden::make('day_of_week'),
                        TextInput::make('day_label')
                            ->readOnly()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn($component, Get $get) => $component->state(
                                $dayNames[$get('day_of_week')] ?? ''
                            )),

                        Toggle::make('is_working_day')
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) use ($timeFields) {
                                if ($state) {
                                    // Día laborable: vuelven los valores por defecto
                                    $set('check_in_time', $get('check_in_time') ?: '08:00:00');
                                    $set('check_out_time', $get('check_out_time') ?: '17:00:00');

                                    return;
                                }

                                // Día no laborable: todas las horas quedan en null
                                foreach ($timeFields as $field) {
                                    $set($field, null);
                                }
                            }),

                        static::rowTime('check_in_time')
                            ->required(fn(Get $get) => (bool) $get('is_working_day')),
                        static::rowTime('break_start_time'),
                        static::rowTime('break_end_time'),
                        static::rowTime('check_out_time')
                            ->required(fn(Get $get) => (bool) $get('is_working_day')),

                        Hidden::make('check_in_tolerance_minutes')->default(0),
                        Hidden::make('check_out_tolerance_minutes')->default(0),
                        Hidden::make('auto_close_after_minutes')->default(10),
                    ]),
            ]);
    }

    /**
     * Tolerancias globales: se copian a las 7 filas.
     */
    protected static function tolerances(): Grid
    {
        return Grid::make(3)->schema([
            static::tolerance('check_in_tolerance', 'check_in_tolerance_minutes', 'fields.check_in_tolerance'),
            static::tolerance('check_out_tolerance', 'check_out_tolerance_minutes', 'fields.check_out_tolerance'),
            static::tolerance(
                'auto_close_after',
                'auto_close_after_minutes',
                'fields.auto_close_after_minutes',
                min: 10,
                default: 10,
            )->required(),
        ]);
    }

    protected static function tolerance(
        string $name,
        string $column,
        string $label,
        int $min = 0,
        int $default = 0,
    ): TextInput {
        return TextInput::make($name)
            ->numeric()
            ->minValue($min)
            ->maxValue(10000)
            ->default($default)
            ->suffix(traduct('sections.work_schedule.minutes'))
            ->dehydrated(false)
            ->live(onBlur: true)
            ->label(traduct($label))
            ->afterStateHydrated(fn($component, $record) => $component->state(
                $record?->days->first()?->{$column} ?? $default
            ))
            ->afterStateUpdated(function ($state, Get $get, Set $set) use ($column, $default) {
                foreach (array_keys($get('days') ?? []) as $key) {
                    $set("days.{$key}.{$column}", (int) ($state ?? $default));
                }
            });
    }

    protected static function time(string $name): SmartTimePicker
    {
        return SmartTimePicker::make($name)
            //->native(false)
            ->seconds(false)
            ->displayFormat('h:i a')
            ->interval(10);
    }

    /**
     * Hora de una fila: deshabilitada si el día no es laborable, y entonces se guarda como null.
     */
    protected static function rowTime(string $name): SmartTimePicker
    {
        return static::time($name)
            ->disabled(fn(Get $get) => ! $get('is_working_day'))
            ->dehydrated()
            ->dehydrateStateUsing(fn($state, Get $get) => $get('is_working_day') ? $state : null);
    }

    protected static function dayNames(): array
    {
        return [
            1 => traductShort('status.day_of_week.monday'),
            2 => traductShort('status.day_of_week.tuesday'),
            3 => traductShort('status.day_of_week.wednesday'),
            4 => traductShort('status.day_of_week.thursday'),
            5 => traductShort('status.day_of_week.friday'),
            6 => traductShort('status.day_of_week.saturday'),
            7 => traductShort('status.day_of_week.sunday'),
        ];
    }
}
