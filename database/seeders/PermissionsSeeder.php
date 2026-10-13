<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        // 1. Resetear la caché de permisos de Spatie antes de empezar
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $path = base_path('database/seeders/json/permissions.json');

        if (! File::exists($path)) {
            $this->command->error('El archivo permissions.json no existe.');
            return;
        }

        $permissions = json_decode(File::get($path), true) ?? [];

        if (empty($permissions)) {
            return;
        }

        $now = now()->toDateTimeString();

        // 2. Traer permisos existentes a memoria en 1 sola consulta
        $existing = Permission::select('name', 'guard_name')
            ->get()
            ->mapWithKeys(fn($p) => ["{$p->name}|{$p->guard_name}" => true])
            ->toArray();

        $permissionsToInsert = [];

        // 3. Filtrar los permisos faltantes en RAM
        foreach ($permissions as $permission) {
            $key = "{$permission['name']}|{$permission['guard_name']}";

            if (isset($existing[$key])) {
                continue;
            }

            $permissionsToInsert[] = [
                'name'       => $permission['name'],
                'guard_name' => $permission['guard_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 4. Inserción masiva (1 sola consulta SQL)
        if (! empty($permissionsToInsert)) {
            Permission::insert($permissionsToInsert);
        }

        // 5. Asignar todos los permisos al Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 6. Limpiar la caché de Spatie nuevamente para registrar los cambios
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}