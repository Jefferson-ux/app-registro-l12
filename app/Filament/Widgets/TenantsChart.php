<?php

namespace App\Filament\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TenantsChart extends ChartWidget
{
    // SHELL: php artisan make:filament-widget TenantsChart --chart
    // Comando para crear este widget 

    // protected ?string $heading = 'Tenants Chart';
    protected ?string $heading = 'Nuevos Tenants (Últimos 6 Meses)'; 
    protected static ?int $sort = 2;

    protected function getData(): array
    {
// Agrupa los tenants creados en los últimos 6 meses
        $data = collect(range(5, 0))->map(function ($monthsAgo) {
            $date = Carbon::now()->subMonths($monthsAgo);
            return [
                'month' => $date->translatedFormat('M Y'),
                'count' => Tenant::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Tenants Registrados',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#3b82f6',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
