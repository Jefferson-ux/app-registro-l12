<?php

namespace App\Filament\Widgets\Attendance\Attendance;

use App\Models\AttendanceSession;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AttendanceStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {

        $startOfWeek = now()->startOfWeek(); 
        $endOfWeek = now()->endOfWeek();
        
        
        // Total de Empleados 
        $employee_count = Employee::count();

        $absent_count = AttendanceSession::query()
            ->where('status', 'absent')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]) // O usa el campo de fecha correspondiente
            ->count();
        
        $late_count = AttendanceSession::query()
            ->where('status', 'late')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]) // O usa el campo de fecha correspondiente
            ->count();

        $present_count = AttendanceSession::query()
            ->where('status', 'present')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]) // O usa el campo de fecha correspondiente
            ->count();

        $incomplete_count = AttendanceSession::query()
            ->where('status', 'incomplete')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]) // O usa el campo de fecha correspondiente
            ->count();

        $leave_count = AttendanceSession::query()
            ->where('status', 'leave')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]) // O usa el campo de fecha correspondiente
            ->count();

        return [
            Stat::make(("employeeCount"), $employee_count)
                ->label(__("sections.employee_count.title")),
            Stat::make(("absentCount"), $absent_count)
                ->label(__("sections.attendance_absent.title")),
            Stat::make(("attendanceLate"), $late_count)
                ->label(__("sections.attendance_late.title")),
            Stat::make(("attendancePresent"), $present_count)
                ->label(__("sections.attendance_present.title")),
            Stat::make(("attendanceLeave"), $leave_count)
                ->label(__("sections.attendance_leave.title")),
            Stat::make(("attendanceIncomplete"), $incomplete_count)
                ->label(__("sections.attendance_incomplete.title")),
        ];
    }
}
