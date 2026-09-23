<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesTenantResource
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Filtro que previene fuga de datos
        if (! $user) {
            return $query->whereNull('tenant_id');
        }

        if ($user->email === config('app.super_admin_email')) {
            return $query;
        }

        return $query->where('tenant_id', $user?->tenant_id);
    }
}