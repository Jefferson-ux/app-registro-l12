<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeviceType: string implements HasLabel
{
    case Kiosk = 'kiosk';

    public function getLabel(): string
    {
        return __('devices.types.kiosk');
    }
}
