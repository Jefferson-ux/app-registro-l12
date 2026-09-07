<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'uuid',
        'name',
        'business_name',
        'tax_id',
        'email',
        'phone',
        'country',
        'timezone',
        'logo',
        'status',
        'trial_ends_at',
    ];

    protected $attributes = [
    'status' => 'trial', // Si creas un Tenant desde un Seeder o código, iniciará como 'trial'
    ];


    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'subscriptions')
            ->withPivot(['status', 'starts_at', 'ends_at', 'cancelled_at'])
            ->withTimestamps();
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function workScheduleDays(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceIncidents(): HasMany
    {
        return $this->hasMany(AttendanceIncident::class);
    }
    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    protected static function booted(): void
        {
            static::creating(function (Tenant $tenant) {
                if (empty($tenant->uuid)) {
                    $tenant->uuid = (string) Str::uuid();
                }
            });
        }

}
