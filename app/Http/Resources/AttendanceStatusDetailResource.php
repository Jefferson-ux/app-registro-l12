<?php

namespace App\Http\Resources;

use App\Traits\ResolvesAttendanceState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceStatusDetailResource extends JsonResource
{
    use ResolvesAttendanceState;

    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [
                'work_date' => now()->toDateString(),
                'current_status' => 'NOT_STARTED',
                'next_action' => 'ENTRY',
                'last_mark' => null,
            ];
        }

        $lastMark = $this->latestMark();
        $currentStatus = $this->determineCurrentStatus();

        return [
            'work_date' => $this->attendance_date?->toDateString() ?? now()->toDateString(),
            'current_status' => $currentStatus,
            'next_action' => $this->determineNextAction($currentStatus),
            'last_mark' => $lastMark
                ? [
                    'type' => $lastMark['type'],
                    'time' => $lastMark['time']->toIso8601String(),
                ]
                : null,
        ];
    }
}
