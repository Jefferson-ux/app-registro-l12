<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;

class PlanesDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Distribución de Planes Activos';
    protected static ?int $sort = 3; // Aparece abajo de tus tarjetas

    protected function getData(): array
    {
        // Contamos cuántas suscripciones activas tiene cada plan
        $conteo = Subscription::where('status', 'active')
            ->selectRaw('plan_id, count(*) as total')
            ->groupBy('plan_id')
            ->get()
            ->pluck('total', 'plan_id');

        $planes = Plan::whereIn('id', $conteo->keys())->get();

        return [
            'datasets' => [
                [
                    'label' => 'Suscripciones',
                    'data' => $conteo->values()->toArray(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'], // Colores Tailwind
                ],
            ],
            'labels' => $planes->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
    // doughnut
        return 'pie';
    }
}
