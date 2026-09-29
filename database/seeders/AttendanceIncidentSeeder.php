<?php

namespace Database\Seeders;

use App\Models\AttendanceIncident;
use App\Models\AttendanceSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceIncidentSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        foreach ($tenants as $tenant) {
            // Usuario administrador o supervisor que resuelve las incidencias
            $adminUser = User::where('tenant_id', $tenant->id)->first();

            // Buscar sesiones con irregularidades en los últimos 15 días
            $problematicSessions = AttendanceSession::where('tenant_id', $tenant->id)
                ->whereIn('status', ['late', 'absent', 'incomplete'])
                ->get();

            foreach ($problematicSessions as $session) {
                // Mapear el tipo de incidencia según el estado de la sesión
                $incidentType = match ($session->status) {
                    'late'       => 'late',
                    'absent'     => 'absence',
                    'incomplete' => fake()->randomElement(['missing_check_in', 'missing_check_out']),
                    default      => 'manual',
                };

                // Evitar duplicar la incidencia para la misma sesión
                if (AttendanceIncident::where('attendance_session_id', $session->id)
                    ->where('incident_type', $incidentType)
                    ->exists()
                ) {
                    continue;
                }

                $status = fake()->randomElement(['pending', 'justified', 'approved', 'rejected', 'cancelled']);
                $isResolved = in_array($status, ['justified', 'approved', 'rejected']);

                AttendanceIncident::create([
                    'tenant_id'             => $tenant->id,
                    'employee_id'           => $session->employee_id,
                    'attendance_session_id' => $session->id,
                    'incident_type'         => $incidentType,
                    'incident_date'         => $session->attendance_date,
                    'description'           => "Incidencia de tipo {$incidentType} generada por el sistema.",
                    'status'                => $status,
                    'resolved_by'           => $isResolved ? $adminUser?->id : null,
                    'resolved_at'           => $isResolved ? now()->subDays(rand(1, 5)) : null,
                    'resolution_notes'      => $isResolved ? 'Revisado y procesado por administración.' : null,
                ]);
            }
        }
    }
}
