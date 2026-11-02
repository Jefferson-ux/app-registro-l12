<?php

namespace App\Filament\Widgets\Attendance;

use App\Models\AttendanceSession;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $employee_count = Employee::where('employment_status', 'active')->count();

        $counts = AttendanceSession::query()
            ->whereDate('attendance_date', today())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            Stat::make('employeeCount', $employee_count)
                ->label(__('sections.employee_count.title')),
            Stat::make('absentCount', $counts->get('absent', 0))
                ->label(__('sections.attendance_absent.title')),
            Stat::make('attendanceLate', $counts->get('late', 0))
                ->label(__('sections.attendance_late.title')),
            Stat::make('attendancePresent', $counts->get('present', 0))
                ->label(__('sections.attendance_present.title')),
            Stat::make('attendanceLeave', $counts->get('leave', 0))
                ->label(__('sections.attendance_leave.title')),
            Stat::make('attendanceIncomplete', $counts->get('incomplete', 0))
                ->label(__('sections.attendance_incomplete.title')),
        ];
    }
}
