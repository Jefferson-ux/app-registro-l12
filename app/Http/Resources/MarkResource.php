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
            'attendance_id'     => $this->id,
            'employee'          => [
                'code'      => $this->employee?->code,
                'full_name' => $this->employee?->full_name,
            ],
            'type'              => $lastMark['type'] ?? null,
            'recorded_at'       => $lastMark ? $lastMark['time']->toIso8601String() : null,
            'work_date'         => $this->attendance_date?->format('Y-m-d'),
            'next_action'       => $this->determineNextAction($status),
            'attendance_status' => $status,
            'punctuality'       => [
                'status'       => $this->status,
                'late_minutes' => $this->late_minutes,
            ],
        ];
    }
}
