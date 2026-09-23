<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (Auth::hasUser()) {
                $user = Auth::user();

                if ($user && $user->tenant_id !== null) {
                    $builder->where($builder->getModel()->getTable() . '.tenant_id', $user->tenant_id);
                }
            }
        });

        static::creating(function (Model $model) {
            if (is_null($model->tenant_id) && Auth::hasUser()) {
                $user = Auth::user();

                if ($user && $user->tenant_id !== null) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}