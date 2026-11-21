<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Device extends Model
{
    use HasApiTokens, HasFactory, BelongsToTenant, Auditable;

    public const ONLINE_THRESHOLD_SECONDS = 180;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'type',
        'latitude',
        'longitude',
        'device_identifier',
    ];

    protected $hidden = [
        'device_identifier',
        'last_ip',
    ];

    protected function casts(): array
    {
        return [
            'type' => DeviceType::class,
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->is_active
            && $this->last_used_at !== null
            && $this->last_used_at->gt(now()->subSeconds(self::ONLINE_THRESHOLD_SECONDS));
    }

    public function issueToken(): string
    {
        $this->tokens()->delete();

        $plain = 'kiosk_' . Str::random(40);

        $this->tokens()->create([
            'name' => 'kiosk',
            'token' => hash('sha256', $plain),
            'abilities' => ['kiosk'],
        ]);

        return $plain;
    }

    public function disconnect(): void
    {
        $this->is_active = false;
        $this->latitude = null;
        $this->longitude = null;
        $this->device_identifier = null;
        $this->last_ip = null;
        $this->save();

        $this->tokens()->delete();
    }

    protected static function booted(): void
    {
        static::deleting(function (Device $device) {
            $device->tokens()->delete();
        });
    }
    public function activate(): void
    {
        $this->is_active = true;
        $this->save();
    }
}
