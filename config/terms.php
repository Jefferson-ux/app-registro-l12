<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versión de Términos y Condiciones
    |--------------------------------------------------------------------------
    |
    | Esta versión se guarda en la tabla tenants al registrar una empresa,
    | para saber qué versión de los términos aceptó cada inquilino.
    |
    */

    'version' => env('TERMS_VERSION', '2026-11'),

];
