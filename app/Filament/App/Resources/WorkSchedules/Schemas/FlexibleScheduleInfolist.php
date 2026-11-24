<?php

namespace App\Filament\App\Resources\WorkSchedules\Schemas;

use App\Models\WorkSchedule;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;

class FlexibleScheduleInfolist
{
    /**
     * Todo lo que se muestra cuando schedule_type = 'flexible'.
     */
    public static function make(): Group
    {
        return Group::make()
            ->visible(fn(WorkSchedule $record): bool => $record->schedule_type === 'flexible')
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
            static::minutesEntry('max_shift_minutes'),
            static::minutesEntry('min_daily_minutes'),
            static::minutesEntry('min_weekly_minutes'),
        ]);
    }

    /**
     * Ventana de entrada (opcional).
     */
    protected static function entryWindow(): Grid
    {
        return Grid::make(2)->schema([
            static::timeEntry('entry_window_start'),
            static::timeEntry('entry_window_end'),
        ]);
    }

    protected static function requiresBreak(): IconEntry
    {
        return IconEntry::make('flexibleRules.requires_break')
            ->label(traduct('fields.requires_break'))
            ->boolean();
    }

    /**
     * Duración del descanso: solo se muestra si requires_break está activo.
     */
    protected static function breakRules(): Grid
    {
        return Grid::make(2)
            ->visible(fn(WorkSchedule $record): bool => (bool) $record->flexibleRules?->requires_break)
            ->schema([
                static::minutesEntry('min_break_minutes'),
                static::minutesEntry('max_break_minutes'),
            ]);
    }

    protected static function restBetweenShifts(): TextEntry
    {
        return static::minutesEntry('min_rest_between_shifts_minutes');
    }

    /**
     * Días esperados como badges; vacío significa que no se registran inasistencias.
     */
    protected static function expectedDays(): TextEntry
    {
        $dayNames = static::dayNames();

        return TextEntry::make('flexibleRules.expected_days')
            ->label(traduct('fields.expected_days'))
            ->badge()
            ->formatStateUsing(fn($state): string => $dayNames[(int) $state] ?? (string) $state)
            ->placeholder(traduct('sections.work_schedule.no_absences_recorded'));
    }

    /**
     * Valor en minutos. El label sale de fields.{columna}.
     */
    protected static function minutesEntry(string $name): TextEntry
    {
        return TextEntry::make("flexibleRules.{$name}")
            ->label(traduct("fields.{$name}"))
            ->numeric()
            ->suffix(' ' . traduct('sections.work_schedule.minutes'))
            ->placeholder(__('empty.generic'));
    }

    /**
     * Hora en formato 12 h; si está vacía muestra el placeholder. El label sale de fields.{columna}.
     */
    protected static function timeEntry(string $name): TextEntry
    {
        return TextEntry::make("flexibleRules.{$name}")
            ->label(traduct("fields.{$name}"))
            ->time('g:i A')
            ->placeholder(__('empty.time'));
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
