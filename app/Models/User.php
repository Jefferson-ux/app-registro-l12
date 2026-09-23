<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, BelongsToTenant;   

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'last_login_at',
        'tenant_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function resolvedAttendanceIncidents(): HasMany
    {
        return $this->hasMany(AttendanceIncident::class, 'resolved_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Inyecta el tenant_id en Spatie automáticamente antes de evaluar cualquier permiso
     */
    public function can($method, $arguments = []): bool
    {
        $tenantId = $this->tenant_id ?? 0;
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        return parent::can($method, $arguments);
    }

    // app/Models/User.php
    /*public function isSuperAdmin(): bool
    {
        // Cambia la condición según cómo identifiques a tu superusuario
        return $this->is_superadmin === true || $this->hasRole('SuperAdmin');
    }*/

}
