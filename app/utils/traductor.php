<?php

use Illuminate\Support\Str;

if (!function_exists('traduct')) {
    function traduct(string $key): string 
    {
        $translation = __($key);
        
        // Si la traducción no existe (devuelve la misma llave "fields.algo"),
        // extrae el nombre del campo y lo formatea bonito para el inglés
        if ($translation === $key) {
            return Str::headline(Str::after($key, '.'));
        }
        
        return $translation;
    }
}

if (!function_exists('traductModel')) {
    function traductModel(string $modelKey, bool $plural = false): string 
    {
        $key = "models.{$modelKey}." . ($plural ? 'plural' : 'singular');
        $translation = __($key);
        
        if ($translation === $key) {
            $base = Str::headline($modelKey);
            return $plural ? Str::plural($base) : $base;
        }
        
        return $translation;
    }
}