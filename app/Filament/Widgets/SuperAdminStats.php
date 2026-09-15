<?php

namespace App\Filament\Widgets;

use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminStats extends StatsOverviewWidget
{


    // CONFIGURACIÓN RESPONSIVA: Controla cuántas tarjetas entran por fila según la pantalla
    protected function getColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            '2xl' => 3, 
        ];
    }


    protected function getStats(): array
    {
        // SHELL: php artisan make:filament-widget SuperAdminStats --stats-overview
        // Comando para crear este widget
// Cálculo estimado de MRR (Ingreso Mensual Recurrente)
        $mrr = Subscription::where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(fn ($sub) => $sub->plan->billing_period === 'yearly' 
                ? $sub->plan->price / 12 
                : $sub->plan->price);
// Cálculo 1: Tasa de conversión de Trial (¿Cuántos pasaron a pagar?)
        $totalEmpresas = Tenant::count();
        $enTrial = Tenant::where('status', 'trial')->count();
        $tasaConversion = $totalEmpresas > 0 ? (($totalEmpresas - $enTrial) / $totalEmpresas) * 100 : 0;

// Cálculo 2: Tasa de Cancelación (Churn Rate) de este mes
        $activasYCanceladas = Subscription::whereIn('status', ['active', 'cancelled'])->count();
        $canceladas = Subscription::where('status', 'cancelled')->count();
        $churnRate = $activasYCanceladas > 0 ? ($canceladas / $activasYCanceladas) * 100 : 0;

// Cálculo 3: Ingreso Promedio por Usuario (ARPU) y LTV Estimado
        $totalActivos = Tenant::where('status', 'active')->count();
        $arpu = $totalActivos > 0 ? ($mrr / $totalActivos) : 0;

               // LTV = ARPU / Churn Rate (Si el churn es 0%, asumimos una retención óptima base)
        $ltvEstimado = $churnRate > 0 ? ($arpu / ($churnRate / 100)) : ($arpu * 12); 

        return [
            // Tarjeta 1: MRR
            Stat::make(__("sections.mrr.title"), 'S/ ' . number_format($mrr, 2))
                ->description(__("sections.mrr.description"))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            // Tarjeta 2: Tenants Activos
            Stat::make(__("sections.active_tenants.title"), Tenant::where('status', 'active')->count())
                ->description(__("sections.active_tenants.description"))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            // Tarjeta 3: Vencimientos cercanos
            Stat::make(__("sections.upcoming_expirations.title"), Subscription::where('status', 'active')
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->count())
                ->description(__("sections.upcoming_expirations.description"))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            // Tarjeta 4: Conversión de Clientes (Métrica de Crecimiento)
            Stat::make(__("sections.trial_conversions.title"), number_format($tasaConversion, 1) . '%')
                ->description(__("sections.trial_conversions.description"))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            // Tarjeta 5: Churn Rate (Métrica de Riesgo Comercial)
            Stat::make(__("sections.cancellation_rate.title"), number_format($churnRate, 1) . '%')
                ->description(__("sections.cancellation_rate.description"))
                ->descriptionIcon('heroicon-m-user-minus')
                ->color('danger'),
        
            // Tarjeta 6: LTV (Lifetime Value) Estimado por Cliente
            Stat::make(__("sections.lifetime_value.title"), 'S/ ' . number_format($ltvEstimado, 2))
                ->description(__("sections.lifetime_value.description"))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
