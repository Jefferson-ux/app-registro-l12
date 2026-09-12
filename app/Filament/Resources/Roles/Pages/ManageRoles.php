<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Spatie\Permission\Models\Permission;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth('6xl')
                ->after(function ($record, array $data) {
                    $allSelectedIds = collect($data)
                        ->filter(fn ($value, $key) => str_starts_with($key, 'permissions_'))
                        ->flatten()
                        ->toArray();

                    $permNames = Permission::whereIn('id', $allSelectedIds)->pluck('name');
                    $record->syncPermissions($permNames);
                }),
        ];
    }
}
