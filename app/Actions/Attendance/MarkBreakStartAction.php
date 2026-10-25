<?php

namespace App\Actions\Attendance;

use App\Exceptions\AttendanceException;
use App\Models\AttendanceSession;
use Carbon\Carbon;

class MarkBreakStartAction
{
    public function execute(AttendanceSession $session, Carbon $now): AttendanceSession
    {
        if (! $session->exists) {
            throw new AttendanceException('Debe registrar su entrada antes de marcar el inicio de un descanso.', 422);
        }

        if ($session->break_start_at) {
            throw new AttendanceException('Ya registraste el inicio de tu descanso anteriormente.', 409);
        }

        $session->update(['break_start_at' => $now]);

        return $session;
    }
}
