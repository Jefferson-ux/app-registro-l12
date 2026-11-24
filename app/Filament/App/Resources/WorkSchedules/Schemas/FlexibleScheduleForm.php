<?php

namespace App\Filament\App\Resources\WorkSchedules\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Harvirsidhu\FilamentTimepicker\SmartTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class FlexibleScheduleForm
{
    /**
     * Todo lo que se muestra cuando schedule_type = 'flexible'.
     */
    public static function make(): Group
    {
        return Group::make()
            ->relationship('flexibleRules')
            ->visible(fn(Get $get) => $get('schedule_type') === 'flexible')
            ->columnSpanFull()
            ->schema([
                static::durations(),
                static::entryWindow(),
                static::requiresBreak(),
                static::breakRules(),
                static::restBetweenShifts(),
                static::expectedDays(),
            ]);
    }

    /**
     * Duraciones base de la jornada flexible.
     */
    protected static function durations(): Grid
    {
        return Grid::make(3)->schema([
            TextInput::make('max_shift_minutes')
                ->label(traduct('fields.max_shift_minutes'))
                ->helperText(traduct('fields.max_shift_minutes_hint'))
                ->numeric()
                ->required()
                ->minValue(60)
                ->maxValue(1440)
                ->default(480)
                ->suffix(traduct('sections.work_schedule.minutes')),

            TextInput::make('min_daily_minutes')
                ->label(traduct('fields.min_daily_minutes'))
                ->numeric()
                ->minValue(0)
                ->lte('max_shift_minutes')
                ->suffix(traduct('sections.work_schedule.minutes')),

            TextInput::make('min_weekly_minutes')
                ->label(traduct('fields.min_weekly_minutes'))
                ->helperText(traduct('fields.min_weekly_minutes_hint'))
                ->numeric()
                ->minValue(0)
                ->suffix(traduct('sections.work_schedule.minutes')),
        ]);
    }

    /**
     * Ventana opcional de entrada (ambas horas o ninguna).
     */
    protected static function entryWindow(): Grid
    {
        return Grid::make(2)->schema([
            static::time('entry_window_start')
                ->label(traduct('fields.entry_window_start'))
                ->required(fn(Get $get) => filled($get('entry_window_end'))),

            static::time('entry_window_end')
                ->label(traduct('fields.entry_window_end'))
                ->helperText(traduct('fields.entry_window_end_hint'))
                ->required(fn(Get $get) => filled($get('entry_window_start'))),
        ]);
    }

    protected static function requiresBreak(): Toggle
    {
        return Toggle::make('requires_break')
            ->label(traduct('fields.requires_break'))
            ->default(false)
            ->live()
            ->afterStateUpdated(function (bool $state, Set $set) {
                if (! $state) {
                    $set('min_break_minutes', null);
                    $set('max_break_minutes', null);
                }
            });
    }

    /**
     * Duración del descanso: solo aplica si requires_break está activo.
     */
    protected static function breakRules(): Grid
    {
        return Grid::make(2)
            ->visible(fn(Get $get) => (bool) $get('requires_break'))
            ->schema([
                TextInput::make('min_break_minutes')
                    ->label(traduct('fields.min_break_minutes'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn(Get $get) => $get('max_break_minutes'))
                    ->lt('max_shift_minutes')
                    ->suffix(traduct('sections.work_schedule.minutes')),

                TextInput::make('max_break_minutes')
                    ->label(traduct('fields.max_break_minutes'))
                    ->numeric()
                    ->minValue(0)
                    ->lt('max_shift_minutes')
                    ->suffix(traduct('sections.work_schedule.minutes')),
            ]);
    }

    protected static function restBetweenShifts(): TextInput
    {
        return TextInput::make('min_rest_between_shifts_minutes')
            ->label(traduct('fields.min_rest_between_shifts_minutes'))
            ->helperText(traduct('fields.min_rest_between_shifts_minutes_hint'))
            ->numeric()
            ->minValue(0)
            ->suffix(traduct('sections.work_schedule.minutes'));
    }

    /**
     * Días esperados (opcional): vacío significa que no se registran inasistencias.
     */
    protected static function expectedDays(): CheckboxList
    {
        return CheckboxList::make('expected_days')
            ->label(traduct('fields.expected_days'))
            ->helperText(traduct('fields.expected_days_hint'))
            ->options(static::dayNames())
            ->columns(4)
            ->dehydrateStateUsing(fn($state) => blank($state)
                ? null
                : array_values(array_map('intval', (array) $state)));
    }

    protected static function time(string $name): SmartTimePicker
    {
        return SmartTimePicker::make($name)
            ->seconds(false)
            ->displayFormat('h:i a')
            ->interval(10);
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
