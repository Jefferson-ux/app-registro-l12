<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // Dentro de tu modelo AuditLog
    public $timestamps = false; // Desactiva el comportamiento automático estándar

    // Y si quieres que Laravel maneje el created_at de forma manual:
    const CREATED_AT = 'created_at';
}
