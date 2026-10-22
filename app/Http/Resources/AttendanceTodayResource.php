<?php

namespace App\Http\Resources;

use App\Traits\ResolvesAttendanceState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceTodayResource extends JsonResource
{
    use ResolvesAttendanceState;

    public function toArray(Request $request): array
    {
        $lastMark = $this->latestMark();
        $status   = $this->determineCurrentStatus();

        return [
            // ===== CAMPOS ORIGINALES (sin cambios) =====
            'id'                  => $this->id,
            'attendance_date'     => $this->attendance_date->format('d-m-Y'),
            'check_in_at'         => $this->check_in_at?->toIso8601String(),
            'check_out_at'        => $this->check_out_at?->toIso8601String(),
            'break_start_at'      => $this->break_start_at?->toIso8601String(),
            'break_end_at'        => $this->break_end_at?->toIso8601String(),
            'scheduled_minutes'   => $this->scheduled_minutes,
            'worked_minutes'      => $this->worked_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes'    => $this->overtime_minutes,
            'punctuality'         => [
                'status'       => $this->status,
                'late_minutes' => $this->late_minutes,
            ],
            'employee'            => new EmployeeResource($this->whenLoaded('employee')),

            // ===== CAMPOS NUEVOS =====
            'attendance_id'       => $this->id,
            'type'                => $lastMark['type'] ?? null,
            'recorded_at'         => $lastMark ? $lastMark['time']->toIso8601String() : null,
            'work_date'           => $this->attendance_date?->format('Y-m-d'),
            'next_action'         => $this->determineNextAction($status),
            'attendance_status'   => $status,
        ];
    }
}