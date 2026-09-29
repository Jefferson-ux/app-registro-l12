<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceTodayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'attendance_date'     => $this->attendance_date->format('d-m-Y'),
            'check_in_at'         => $this->check_in_at?->toIso8601String(),
            'check_out_at'        => $this->check_out_at?->toIso8601String(),
            'scheduled_minutes'   => $this->scheduled_minutes,
            'worked_minutes'      => $this->worked_minutes,
            'late_minutes'        => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'overtime_minutes'    => $this->overtime_minutes,
            'status'              => $this->status,
            'employee'            => new EmployeeResource($this->whenLoaded('employee')),
        ];
    }
}
