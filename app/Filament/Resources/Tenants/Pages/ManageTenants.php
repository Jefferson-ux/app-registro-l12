<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageTenants extends ManageRecords
{
    protected static string $resource = TenantResource::class;


    public function getTabs(): array
    {
        return [
            'all' => Tab::make('all')
                ->icon('heroicon-m-rectangle-stack')
                ->label(traduct('fields.all')),
                

            'trial' => Tab::make('trial')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'trial'))
                ->icon('heroicon-m-clock') // ⏳ Reloj para periodo de prueba
                ->extraAttributes([
                    'style' => 'border-bottom: 3px solid #0284c7; color: #0284c7;' // Azul informativo
                ])
                ->label(traduct('status.trials')),

            'active' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->icon('heroicon-m-check-circle') // ✅ Check para activos
                ->extraAttributes([
                    'style' => 'border-bottom: 3px solid #16a34a; color: #16a34a;' // Verde éxito
                ])
                ->label(traduct('status.actives')),

            'suspended' => Tab::make('Suspendidos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended'))
                ->icon('heroicon-m-exclamation-triangle') // ⚠️ Alerta para suspendidos
                ->extraAttributes([
                    'style' => 'border-bottom: 3px solid #ca8a04; color: #ca8a04;' // Amarillo advertencia
                ])
                ->label(traduct('status.suspendeds')),

            'inactive' => Tab::make('Inactivos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inactive'))
                ->icon('heroicon-m-x-circle') // ❌ Equis para inactivos
                ->extraAttributes([
                    'style' => 'border-bottom: 3px solid #dc2626; color: #dc2626;' // Rojo peligro
                ])
                ->label(traduct('status.inactives')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }


}
