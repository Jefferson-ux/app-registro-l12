<?php

namespace App\Enums;

enum RoleColor: string
{
    case Gray = 'gray';
    case Red = 'red';
    case Blue = 'blue';
    case Green = 'green';
    case Amber = 'amber';
    case Indigo = 'indigo';

    public function label(): string
    {
        return match($this) {
            self::Gray => 'Gris',
            self::Red => 'Rojo',
            self::Blue => 'Azul',
            self::Green => 'Verde',
            self::Amber => 'Ámbar',
            self::Indigo => 'Índigo',
        };
    }
}