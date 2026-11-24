<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class WorkFlexibleRule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'work_schedule_id',
        'max_shift_minutes',
        'min_daily_minutes',
        'min_weekly_minutes',
        'entry_window_start',
        'entry_window_end',
        'requires_break',
        'min_break_minutes',
        'max_break_minutes',
        'min_rest_between_shifts_minutes',
        'expected_days',
    ];

    protected $casts = [
        'max_shift_minutes' => 'integer',
        'min_daily_minutes' => 'integer',
        'min_weekly_minutes' => 'integer',
        'requires_break' => 'boolean',
        'min_break_minutes' => 'integer',
        'max_break_minutes' => 'integer',
        'min_rest_between_shifts_minutes' => 'integer',
        'expected_days' => 'array',
    ];

    protected static function booted(): void
    {
        // Red de seguridad además de las validaciones de Filament.
        static::saving(function (self $rule) {
            if ($rule->max_shift_minutes < 60 || $rule->max_shift_minutes > 1440) {
                throw new InvalidArgumentException('max_shift_minutes debe estar entre 60 y 1440.');
            }

            if ($rule->min_daily_minutes !== null && $rule->min_daily_minutes > $rule->max_shift_minutes) {
                throw new InvalidArgumentException('min_daily_minutes no puede superar max_shift_minutes.');
            }

            if (($rule->entry_window_start === null) !== ($rule->entry_window_end === null)) {
                throw new InvalidArgumentException('La ventana de entrada requiere inicio y fin.');
            }

            if (
                $rule->min_break_minutes !== null && $rule->max_break_minutes !== null
                && $rule->min_break_minutes > $rule->max_break_minutes
            ) {
                throw new InvalidArgumentException('min_break_minutes no puede superar max_break_minutes.');
            }
        });
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
