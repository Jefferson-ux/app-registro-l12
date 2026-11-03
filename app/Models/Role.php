<?php

namespace App\Models;

use App\Enums\RoleColor;
use App\Traits\BelongsToTenant;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{

    protected $fillable = [
        'name',
        'guard_name',
        'tenant_id',
        'label',
        'description',
        'color',
        'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'color' => RoleColor::class,
    ];

    protected static function booted(): void
    {
        static::creating(function ($role) {
            if (empty($role->tenant_id)) {
                $role->tenant_id = filament()->getTenant()?->id
                    ?? auth()->user()?->tenant_id
                    ?? auth()->user()?->current_tenant_id; // Ajusta según la propiedad de tu modelo User
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
