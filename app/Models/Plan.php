<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
