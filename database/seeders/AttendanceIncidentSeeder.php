<?php

namespace Database\Seeders;

use App\Models\AttendanceIncident;
use App\Models\AttendanceSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceIncidentSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();

        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->error('No hay Tenants registrados. Ejecuta TenantSeeder primero.');
            return;
        }

        $now = now()->toDateTimeString();

        $existingIncidents = AttendanceIncident::whereIn('tenant_id', $tenants->pluck('id'))
            ->select('attendance_session_id', 'incident_type')
            ->get()
            ->mapWithKeys(fn($item) => ["{$item->attendance_session_id}|{$item->incident_type}" => true])
            ->toArray();

        $statuses = ['pending', 'justified', 'approved', 'rejected', 'cancelled'];
        $incompleteTypes = ['missing_check_in', 'missing_check_out'];

        $incidentsToInsert = [];

        foreach ($tenants as $tenant) {
            $adminUser = User::where('tenant_id', $tenant->id)->first();

            $problematicSessions = AttendanceSession::where('tenant_id', $tenant->id)
                ->whereIn('status', ['late', 'absent', 'incomplete'])
                ->get();

            foreach ($problematicSessions as $session) {
                $incidentType = match ($session->status) {
                    'late'       => 'late',
                    'absent'     => 'absence',
                    'incomplete' => $incompleteTypes[array_rand($incompleteTypes)],
                    default      => 'manual',
                };

                $key = "{$session->id}|{$incidentType}";

                if (isset($existingIncidents[$key])) {
                    continue;
                }

                $status = $statuses[array_rand($statuses)];
                $isResolved = in_array($status, ['justified', 'approved', 'rejected']);

                $incidentsToInsert[] = [
                    'tenant_id'             => $tenant->id,
                    'employee_id'           => $session->employee_id,
                    'attendance_session_id' => $session->id,
                    'incident_type'         => $incidentType,
                    'incident_date'         => $session->attendance_date,
                    'description'           => "Incidencia de tipo {$incidentType} generada por el sistema.",
                    'status'                => $status,
                    'resolved_by'           => $isResolved ? $adminUser?->id : null,
                    'resolved_at'           => $isResolved ? now()->subDays(rand(1, 5))->toDateTimeString() : null,
                    'resolution_notes'      => $isResolved ? 'Revisado y procesado por administración.' : null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            }
        }

        if (! empty($incidentsToInsert)) {
            DB::transaction(function () use ($incidentsToInsert) {
                foreach (array_chunk($incidentsToInsert, 500) as $chunk) {
                    AttendanceIncident::insert($chunk);
                }
            });
        }
    }
}