<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\Employee;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    protected $model = AttendanceSession::class;

    public function definition(): array
    {
        $employee = Employee::inRandomOrder()->first();
        $date = fake()->dateTimeBetween('-15 days', 'now')->format('Y-m-d');

        $status = fake()->randomElement(['present', 'late', 'absent', 'incomplete', 'holiday', 'leave']);

        $scheduledMinutes = 480;
        $checkIn = null;
        $checkOut = null;
        $workedMinutes = 0;
        $lateMinutes = 0;
        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;

        switch ($status) {
            case 'present':
                $checkIn = Carbon::parse("{$date} 08:00:00");
                $checkOut = Carbon::parse("{$date} 17:00:00");
                $workedMinutes = 480;
                break;

            case 'late':
                $lateMinutes = rand(10, 45);
                $checkIn = Carbon::parse("{$date} 08:00:00")->addMinutes($lateMinutes);
                $checkOut = Carbon::parse("{$date} 17:00:00");
                $workedMinutes = max(0, $scheduledMinutes - $lateMinutes);
                break;

            case 'incomplete':
                $checkIn = Carbon::parse("{$date} 08:00:00");
                $checkOut = null;
                $workedMinutes = 240;
                break;

            case 'absent':
            case 'holiday':
            case 'leave':
                // Sin horas marcadas
                break;
        }

        return [
            'tenant_id'           => $employee?->tenant_id ?? Tenant::inRandomOrder()->value('id'),
            'employee_id'         => $employee?->id,
            'attendance_date'     => $date,
            'check_in_at'         => $checkIn,
            'check_out_at'        => $checkOut,
            'scheduled_minutes'   => $scheduledMinutes,
            'worked_minutes'      => $workedMinutes,
            'late_minutes'        => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes'    => $overtimeMinutes,
            'status'              => $status,
        ];
    }
}
