<?php

namespace App\Filament\Widgets\Attendance;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance.welcome-widget';
    protected static ?int $sort = -3;
    protected int | string | array $columnSpan = 'full';
}
