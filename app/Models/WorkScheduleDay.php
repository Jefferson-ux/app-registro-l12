<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkScheduleDay extends Model
{
    protected $fillable = [
        'tenant_id',
        'work_schedule_id',
        'day_of_week',
        'is_working_day',
        'check_in_time',
        'check_out_time',
        'break_start_time',
        'break_end_time',
        'check_in_tolerance_minutes',
        'check_out_tolerance_minutes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_working_day' => 'boolean',
            'check_in_time' => 'datetime:H:i:s',
            'check_out_time' => 'datetime:H:i:s',
            'break_start_time' => 'datetime:H:i:s',
            'break_end_time' => 'datetime:H:i:s',
            'check_in_tolerance_minutes' => 'integer',
            'check_out_tolerance_minutes' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }
}
