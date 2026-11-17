<?php

namespace App\Filament\Widgets\Attendance;

use App\Models\AttendanceSession;
use Filament\Widgets\Widget;

class RecentAttendanceSession extends Widget
{
    protected string $view = 'filament.widgets.attendance.attendance.recent-attendance-session';

    protected int | string | array $columnSpan = [
        'default' => 'full',
        '2xl' => '2',
    ];

    public string $search = '';

    protected function getViewData(): array
    {
        $term = trim($this->search);

        $sessions = AttendanceSession::with('employee')
            ->whereDate('attendance_date', today())
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . $term . '%';

                $q->whereHas('employee', function ($e) use ($like) {
                    $e->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('employee_code', 'like', $like);
                });
            })
            ->get();
        $columns = ['check_in_at', 'break_start_at', 'break_end_at', 'check_out_at'];

        $events = $sessions->flatMap(function ($s) use ($columns) {
            return collect($columns)
                ->filter(fn($column) => $s->{$column})
                ->map(fn($column) => [
                    'employee' => $s->employee,
                    'column'   => $column,
                    'at'       => $s->{$column},
                    'status'   => $column === 'check_in_at' ? $s->status : null,
                ])
                ->values();
        })->sortByDesc('at')->values();

        return ['events' => $events];
    }

    protected static function formatHumanTime($state): string
    {
        if (!$state) {
            return '-';
        }

        $date = \Carbon\Carbon::parse($state);
        $now = \Carbon\Carbon::now();

        $diffInSeconds = (int) $date->diffInSeconds($now);
        $diffInMinutes = (int) $date->diffInMinutes($now);
        $diffInHours = (int) $date->diffInHours($now);

        if ($diffInSeconds < 60) {
            return "hace {$diffInSeconds} s";
        }

        if ($diffInMinutes < 60) {
            return "hace {$diffInMinutes} m";
        }

        if ($diffInHours < 2) {
            $minsRestantes = $diffInMinutes % 60;
            return "hace {$diffInHours} h y {$minsRestantes} m";
        }

        return $date->translatedFormat('h:i A - d/m/Y ');
    }
}
