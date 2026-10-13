<?php

namespace Database\Factories;

use App\Models\AttendanceIncident;
use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceIncident>
 */
class AttendanceIncidentFactory extends Factory
{
    protected $model = AttendanceIncident::class;

    public function definition(): array
    {
        $employee = Employee::inRandomOrder()->first();
        $session = AttendanceSession::where('employee_id', $employee?->id)->inRandomOrder()->first();

        $incidentType = fake()->randomElement([
            'late',
            'absence',
            'early_leave',
            'missing_check_in',
            'missing_check_out',
            'manual',
        ]);

        $status = fake()->randomElement(['pending', 'justified', 'approved', 'rejected', 'cancelled']);
        $isResolved = in_array($status, ['justified', 'approved', 'rejected']);

        $resolver = $isResolved
            ? User::where('tenant_id', $employee?->tenant_id)->inRandomOrder()->first()
            : null;

        return [
            'tenant_id'             => $employee?->tenant_id ?? Tenant::inRandomOrder()->value('id'),
            'employee_id'           => $employee?->id,
            'attendance_session_id' => $session?->id,
            'incident_type'         => $incidentType,
            'incident_date'         => $session?->attendance_date ?? fake()->dateTimeBetween('-15 days', 'now')->format('Y-m-d'),
            'description'           => fake()->sentence(),
            'status'                => $status,
            'resolved_by'           => $resolver?->id,
            'resolved_at'           => $isResolved ? fake()->dateTimeBetween('-10 days', 'now') : null,
            'resolution_notes'      => $isResolved ? fake()->sentence() : null,
        ];
    }
}
