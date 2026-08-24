<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'business_name',
        'tax_id',
        'email',
        'phone',
        'country',
        'timezone',
        'logo',
        'status'
    ];


    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }
}
