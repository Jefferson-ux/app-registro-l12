<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (auth()->check()) {
                $user = auth()->user();

                // Asigna el tenant_id si el modelo aún no lo tiene definido
                if (is_null($model->tenant_id)) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }
}