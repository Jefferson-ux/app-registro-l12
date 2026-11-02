<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class AppDashboard extends BaseDashboard
{
    public function getTitle(): string | Htmlable
    {
        $tenantName = auth()->user()?->tenant?->name;

        return $tenantName
            ? __('widgets.dashboard.title_tenant', ['name' => $tenantName])
            : __('widgets.dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('widgets.dashboard.navigation');
    }
}
