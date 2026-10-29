<?php

namespace App\Filament\Widgets\Attendance\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use Filament\Widgets\Widget;

class RecentAttendanceSession extends Widget
{
    protected string $view = 'filament.widgets.attendance.attendance.recent-attendance-session';

    protected int | string | array $columnSpan = [
        'default' => 'full',
        '2xl' => '2', // Ocupa la mitad si el grid del dashboard es de 2 columnas
    ];

protected function getViewData(): array
{
    $sessions = AttendanceSession::with('employee')
        ->whereNotNull('check_in_at')
        ->latest('updated_at')
        ->limit(30)
        ->get();

    $events = $sessions->flatMap(function ($s) {
        $rows = collect();

        $rows->push([
            'employee' => $s->employee,
            'type'     => 'Entrada',
            'at'       => $s->check_in_at,
            'status'   => $s->status,
        ]);

        if ($s->check_out_at) {
            $rows->push([
                'employee' => $s->employee,
                'type'     => 'Salida',
                'at'       => $s->check_out_at,
                'status'   => null,
            ]);
        }

        return $rows;
    })->sortByDesc('at')->take(30)->values();

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


        if ($diffInSeconds === 1) {
            return "hace {$diffInSeconds} segundo";
        }

        if ($diffInSeconds < 60) {
            return "hace {$diffInSeconds} segundos";
        }

        if ($diffInMinutes === 1) {
            return "hace {$diffInMinutes} minuto";
        }

        if ($diffInMinutes < 60) {
            return "hace {$diffInMinutes} minutos";
        }

        if ($diffInHours === 1) {
            $mins = $date->copy()->addHour()->diffInMinutes($now); // o simplemente el módulo
            return "hace 1 hora y " . ($diffInMinutes % 60) . " minutos";
        }

        if ($diffInHours < 8) {
            $minsRestantes = $diffInMinutes % 60;
            return "hace {$diffInHours} horas y {$minsRestantes} minutos";
        }

        return $date->translatedFormat('Y-m-d h:i A'); // Actualizado a formato 12h con AM/PM por si lo prefieres aquí
    }

}
