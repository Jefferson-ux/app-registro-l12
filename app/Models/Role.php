<?php

namespace App\Models;

use App\Enums\RoleColor;
use App\Traits\BelongsToTenant;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use BelongsToTenant;  

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

     
}
