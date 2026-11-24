<?php

namespace App\Filament\App\Resources\WorkSchedules\Schemas;

use App\Models\WorkSchedule;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;

class FixedScheduleInfolist
{
    /**
     * Todo lo que se muestra cuando schedule_type = 'fixed'.
     */
    public static function make(): RepeatableEntry
    {
        return RepeatableEntry::make('days')
            ->label(traduct('sections.work_schedule.tab_schedule'))
            ->visible(fn(WorkSchedule $record): bool => $record->schedule_type === 'fixed')
            ->placeholder(__('empty.generic'))
            ->columns(4)
            ->schema([
                static::dayEntry(),

                IconEntry::make('is_working_day')
                    ->label(traduct('fields.is_working_day'))
                    ->boolean(),

                static::timeEntry('check_in_time', 'fields.check_in_time'),
                static::timeEntry('check_out_time', 'fields.check_out_time'),
                static::timeEntry('break_start_time', 'fields.break_start_time'),
                static::timeEntry('break_end_time', 'fields.break_end_time'),

                static::toleranceEntry('check_in_tolerance_minutes', 'fields.check_in_tolerance'),
                static::toleranceEntry('check_out_tolerance_minutes', 'fields.check_out_tolerance'),
            ]);
    }

    /**
     * Día de la semana como badge con su nombre traducido.
     */
    protected static function dayEntry(): TextEntry
    {
        $dayNames = static::dayNames();

        return TextEntry::make('day_of_week')
            ->label(traduct('fields.day_of_week'))
            ->badge()
            ->formatStateUsing(fn(int|string|null $state): string => $dayNames[(int) $state] ?? __('empty.generic'));
    }

    /**
     * Hora en formato 12 h; si está vacía muestra el placeholder.
     */
    protected static function timeEntry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label(traduct($label))
            ->time('g:i A')
            ->placeholder(__('empty.time'));
    }

    /**
     * Tolerancia en minutos.
     */
    protected static function toleranceEntry(string $name, string $label): TextEntry
    {
        return TextEntry::make($name)
            ->label(traduct($label))
            ->numeric()
            ->suffix(' min');
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
