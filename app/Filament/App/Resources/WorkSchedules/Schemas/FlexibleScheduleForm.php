<?php

namespace App\Filament\App\Resources\WorkSchedules\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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
                ->label('Tiempo máximo de jornada')
                ->helperText('Tiempo máximo transcurrido desde la entrada (incluye descanso). Pasado este tiempo sin salida, la jornada queda incompleta.')
                ->numeric()
                ->required()
                ->minValue(60)
                ->maxValue(1440)
                ->default(480)
                ->suffix(traduct('sections.work_schedule.minutes')),

            TextInput::make('min_daily_minutes')
                ->label('Mínimo diario')
                ->numeric()
                ->minValue(0)
                ->lte('max_shift_minutes')
                ->suffix(traduct('sections.work_schedule.minutes')),

            TextInput::make('min_weekly_minutes')
                ->label('Mínimo semanal')
                ->helperText('Valor informativo para reportes; no bloquea las marcaciones.')
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
                ->label('Inicio de ventana de entrada')
                ->required(fn(Get $get) => filled($get('entry_window_end'))),

            static::time('entry_window_end')
                ->label('Fin de ventana de entrada')
                ->helperText('Si la hora final es menor que la inicial, la ventana cruza medianoche.')
                ->required(fn(Get $get) => filled($get('entry_window_start'))),
        ]);
    }

    protected static function requiresBreak(): Toggle
    {
        return Toggle::make('requires_break')
            ->label('Requiere descanso')
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
                    ->label('Descanso mínimo')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn(Get $get) => $get('max_break_minutes'))
                    ->lt('max_shift_minutes')
                    ->suffix(traduct('sections.work_schedule.minutes')),

                TextInput::make('max_break_minutes')
                    ->label('Descanso máximo')
                    ->numeric()
                    ->minValue(0)
                    ->lt('max_shift_minutes')
                    ->suffix(traduct('sections.work_schedule.minutes')),
            ]);
    }

    protected static function restBetweenShifts(): TextInput
    {
        return TextInput::make('min_rest_between_shifts_minutes')
            ->label('Descanso entre turnos')
            ->helperText('Descanso obligatorio entre una salida y la siguiente entrada.')
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
            ->label('Días esperados')
            ->helperText('Déjalo vacío si no se registran inasistencias.')
            ->options(static::dayNames())
            ->columns(4)
            ->dehydrateStateUsing(fn($state) => blank($state)
                ? null
                : array_values(array_map('intval', (array) $state)));
    }

    protected static function time(string $name): TimePicker
    {
        return TimePicker::make($name)
            ->seconds(false)
            ->displayFormat('h:i a');
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
