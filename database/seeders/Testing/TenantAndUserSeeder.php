<?php

namespace Database\Seeders\Testing;

use App\Actions\AssignRoleToUserAction;
use App\Actions\CreateTenantAdminRoleAction;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantAndUserSeeder extends Seeder
{
    public function run(
        CreateTenantAdminRoleAction $createAdminRole,
        AssignRoleToUserAction $assignRole,
    ): void {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        DB::transaction(function () use ($createAdminRole, $assignRole, $registrar) {
            $tenant = Tenant::create([
                'uuid' => (string) Str::uuid(),
                'name' => 'Empresa Pruebas Asistencia',
                'business_name' => 'empresa pruebas asistencia s.a.c.',
                'tax_id' => '20' . fake()->unique()->numerify('#########'),
                'email' => 'pruebas@empresa.pe',
                'phone' => '+51 9' . fake()->numerify('########'),
                'country' => 'Peru',
                'timezone' => 'America/Lima',
                'logo' => null,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(30),
                'terms_accepted_at' => now(),
                'terms_version' => config('terms.version', '1.0'),
            ]);

            $adminUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin Pruebas',
                'email' => 'admin.pruebas@empresa.pe',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);

            $createAdminRole->execute($tenant->id);
            $assignRole->execute($adminUser, 'admin');

            $registrar->setPermissionsTeamId($tenant->id);

            $supervisorRole = Role::firstOrCreate(
                [
                    'name' => 'supervisor_asistencia',
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->id,
                ],
                [
                    'label' => 'Supervisor de Asistencia',
                    'description' => 'Gestiona sesiones, registros, incidencias y consulta horarios y empleados.',
                    'is_protected' => false,
                ]
            );

            $supervisorRole->syncPermissions(
                Permission::whereIn('name', [
                    'ViewAny:AttendanceSession',
                    'View:AttendanceSession',
                    'Create:AttendanceSession',
                    'Update:AttendanceSession',
                    'ViewAny:AttendanceRecord',
                    'View:AttendanceRecord',
                    'ViewAny:AttendanceIncident',
                    'View:AttendanceIncident',
                    'Update:AttendanceIncident',
                    'ViewAny:Employee',
                    'View:Employee',
                    'ViewAny:EmployeeSchedule',
                    'View:EmployeeSchedule',
                    'ViewAny:WorkSchedule',
                    'View:WorkScheduleDay',
                ])->get()
            );

            $supervisorUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Supervisor Operativo',
                'email' => 'supervisor.pruebas@empresa.pe',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $assignRole->execute($supervisorUser, 'supervisor_asistencia');

            $viewerRole = Role::firstOrCreate(
                [
                    'name' => 'analista_lectura',
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->id,
                ],
                [
                    'label' => 'Analista Solo Lectura',
                    'description' => 'Solo consulta asistencia, empleados, departamentos y cargos.',
                    'is_protected' => false,
                ]
            );

            $viewerRole->syncPermissions(
                Permission::whereIn('name', [
                    'ViewAny:AttendanceSession',
                    'View:AttendanceSession',
                    'ViewAny:AttendanceRecord',
                    'View:AttendanceRecord',
                    'ViewAny:Employee',
                    'View:Employee',
                    'ViewAny:Department',
                    'View:Department',
                    'ViewAny:Position',
                    'View:Position',
                ])->get()
            );

            $viewerUser = User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Analista Lectura',
                'email' => 'analista.pruebas@empresa.pe',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]);
            $assignRole->execute($viewerUser, 'analista_lectura');
        });

        $registrar->setPermissionsTeamId($previousTeamId);
        $registrar->forgetCachedPermissions();
    }
}
