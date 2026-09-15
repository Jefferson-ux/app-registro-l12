<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Spatie\Permission\PermissionRegistrar;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
                CreateAction::make()
                    ->after(function ($record, array $data) {
                        $tenantId = $record->tenant_id ?? 0;
                        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

                        $roleNames = Role::whereIn('id', $data['roles'] ?? [])->pluck('name');
                        $record->syncRoles($roleNames);
                    })
                    ,
                ];
    }
}





// toni92@yahoo.com UserCreator
// chelsie.walter@feeney.com Variable Plan1
// pwalker@gmail.com Superadmin
// bernadette.ferry@gmail.com Superadmin error
