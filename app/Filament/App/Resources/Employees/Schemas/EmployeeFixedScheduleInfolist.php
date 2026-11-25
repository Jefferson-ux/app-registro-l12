<?php

namespace App\Filament\App\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Schemas\Components\View;

class EmployeeFixedScheduleInfolist
{
    public static function make(): View
    {
        return View::make('filament.app.resources.employees.schemas.fixed-schedule-table')
            ->visible(fn(Employee $record): bool => $record->activeWorkSchedule()?->schedule_type === 'fixed')
            ->columnSpanFull()
            ->viewData(function (Employee $record): array {
                $daysByIso = $record->activeWorkSchedule()
                    ?->days()
                    ->get()
                    ->keyBy('day_of_week') ?? collect();

                $rows = collect(range(1, 7))->map(function (int $dayNumber) use ($daysByIso): array {
                    $day = $daysByIso->get($dayNumber);
                    $isWorking = (bool) ($day?->is_working_day ?? false);

                    return [
                        'day_label' => static::dayNames()[$dayNumber] ?? (string) $dayNumber,
                        'is_working' => $isWorking,
                        'check_in' => static::formatTime($day?->check_in_time),
                        'break_start' => static::formatTime($day?->break_start_time),
                        'break_end' => static::formatTime($day?->break_end_time),
                        'check_out' => static::formatTime($day?->check_out_time),
                        'tol_in' => static::formatMinutes($day?->check_in_tolerance_minutes),
                        'tol_out' => static::formatMinutes($day?->check_out_tolerance_minutes),
                    ];
                })->all();

                return [
                    'rows' => $rows,
                ];
            });
    }

    protected static function formatTime(mixed $value): string
    {
        if (blank($value)) {
            return __('empty.time');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('g:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected static function formatMinutes(mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('empty.generic');
        }

        return $value . ' min';
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
