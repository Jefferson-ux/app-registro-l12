<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // 1. FILTRO AUTOMÁTICO PARA SELECTS Y CONSULTAS
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            $user = Auth::user();

            if ($user && $user->tenant_id) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
            }
        });

        // 2. ASIGNACIÓN AUTOMÁTICA AL CREAR REGISTROS
        static::creating(function (Model $model) {
            $user = Auth::user();

            if ($user && $user->tenant_id && is_null($model->tenant_id)) {
                $model->tenant_id = $user->tenant_id;
            }
        });
    }

    // Relación
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}