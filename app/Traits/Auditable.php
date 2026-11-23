<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::writeAudit($model, 'created', null, $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model) {
            $new = $model->auditableAttributes($model->getChanges());

            if (empty($new)) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $new);

            static::writeAudit($model, 'updated', $old, $new);
        });

        static::deleted(function (Model $model) {
            static::writeAudit($model, 'deleted', $model->auditableAttributes($model->getOriginal()), null);
        });
    }

    protected function auditableAttributes(array $attributes): array
    {
        $excluded = array_merge(
            ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'],
            $this->auditExcluded ?? []
        );

        return array_diff_key($attributes, array_flip($excluded));
    }

    protected static function writeAudit(Model $model, string $event, ?array $old, ?array $new): void
    {
        $user = auth()->user();
        $tenantId = $model->tenant_id ?? $user?->tenant_id;

        $tenant = null;

        if ($tenantId) {
            $tenant = $user?->tenant_id === $tenantId
                ? $user->tenant
                : Tenant::find($tenantId);
        }

        AuditLog::create([
            'tenant_id'   => $tenantId,
            'tenant_name' => $tenant?->name,
            'tenant_ruc'  => $tenant?->tax_id,
            'branch_id'   => $model->branch_id ?? null,
            'user_id'     => $user?->id,
            'user_name'   => $user?->name,
            'action'      => class_basename($model) . '.' . $event,
            'entity_type' => $model::class,
            'entity_id'   => $model->getKey(),
            'old_values'  => $old,
            'new_values'  => $new,
            'ip_address'  => request()?->ip(),
        ]);
    }
}