<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_employees',
        'max_users',
        'max_branches',
        'price',
        'currency',
        'billing_period',
        'status'
    ];
    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'price' => 'decimal:2', // Asegura 2 decimales al formatear
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'subscriptions')
            ->withPivot(['status', 'starts_at', 'ends_at', 'cancelled_at'])
            ->withTimestamps();
    }
}
