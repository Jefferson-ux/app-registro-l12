<?php

namespace App\Actions;

use App\Enums\RoleColor;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class CreateTenantAdminRoleAction
{
    private array $appModels = [
        'Branch', 'Department', 'Employee', 'EmployeeSchedule',
        'Holiday', 'Position', 'WorkSchedule', 'WorkScheduleDay',
        'AttendanceRecord', 'AttendanceSession', 'AttendanceIncident',
        'Role','User'
    ];

    public function execute(int $tenantId): Role
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);   

        $description = "Rol con acceso completo al panel de la empresa.
Puede gestionar todos los módulos: empleados, sucursales, departamentos, cargos, horarios, asistencia y roles del sistema.
Tiene permiso para crear, editar y eliminar usuarios, así como asignarles roles.
Este rol no puede eliminarse ni transferirse a otro usuario.";

        $role = Role::firstOrCreate(
            [
                'name' => 'admin',
                'guard_name' => 'web',
                'tenant_id' => $tenantId,
            ],
            [
                'label' => 'Administrador',
                'description' => $description,
                'is_protected' => true,
                'color' => RoleColor::Amber->value,
            ]
        );

        $permissions = Permission::all()
            ->filter(fn ($p) => in_array(explode(':', $p->name)[1] ?? '', $this->appModels))
            ->pluck('name');

        $role->givePermissionTo($permissions);

        return $role;
    }
}