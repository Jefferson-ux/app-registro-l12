<?php

namespace App\Http\Resources;

use App\Traits\ResolvesAttendanceState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarkResource extends JsonResource
{
    use ResolvesAttendanceState;

    public function toArray(Request $request): array
    {
        $lastMark = $this->latestMark();
        $status   = $this->determineCurrentStatus();

        return [
            'id'                => $this->id,
            'attendance_id'     => $this->id,
            'employee'          => new EmployeeResource($this->whenLoaded('employee')),
            'type'              => $lastMark['type'] ?? null,
            'work_date'         => $this->attendance_date?->format('Y-m-d'),
            'next_action'       => $this->determineNextAction($status),
            'recorded_at'       => $lastMark ? $lastMark['time']->toIso8601String() : null,
            'attendance_date'   => $this->attendance_date->format('d-m-Y'),
            'attendance_status' => $status,
            'punctuality'       => [
                'status'        => $this->status,
                'late_minutes'  => $this->late_minutes,
            ],
            'check_in_at'         => $this->check_in_at?->toIso8601String(),
            'check_out_at'        => $this->check_out_at?->toIso8601String(),
            'break_start_at'      => $this->break_start_at?->toIso8601String(),
            'break_end_at'        => $this->break_end_at?->toIso8601String(),
        ];
    }
}
