<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use Carbon\Carbon;

class MarkBreakEndAction
{
    public function execute(AttendanceSession $session, Carbon $now): AttendanceSession
    {
        if (! $session->exists || ! $session->check_in_at) {
            throw new AttendanceException('No existe una sesión activa para registrar el fin de descanso.', 422);
        }

        if ($session->check_out_at) {
            throw new AttendanceException('La jornada de hoy ya está completada.', 409);
        }

        if (! $session->break_start_at) {
            throw new AttendanceException('El empleado no ha salido a su Break respectivo.', 422);
        }

        if ($session->break_end_at) {
            throw new AttendanceException('Ya registraste el fin de tu descanso anteriormente.', 409);
        }

        $session->update(['break_end_at' => $now]);

        return $session;
    }
}
