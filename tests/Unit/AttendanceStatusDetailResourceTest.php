<?php

namespace Tests\Unit;

use App\Http\Resources\AttendanceStatusDetailResource;
use App\Models\AttendanceSession;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttendanceStatusDetailResourceTest extends TestCase
{
    public function test_it_resolves_attendance_status_from_the_shared_trait(): void
    {
        $session = new AttendanceSession([
            'attendance_date' => '2026-10-24',
            'check_in_at' => '2026-10-24 08:00:00',
            'check_out_at' => '2026-10-24 17:00:00',
            'scheduled_minutes' => 480,
            'worked_minutes' => 480,
        ]);
        $session->setRelation('employee', new Employee([
            'employee_code' => 'EMP-001',
        ]));

        $data = (new AttendanceStatusDetailResource($session))
            ->toArray(Request::create('/'));

        $this->assertSame('EMP-001', $data['employee_code']);
        $this->assertSame('2026-10-24', $data['work_date']);
        $this->assertSame('COMPLETED', $data['current_status']);
        $this->assertSame('COMPLETED', $data['next_action']);
        $this->assertSame('EXIT', $data['last_mark']['type']);
        $this->assertSame(
            Carbon::parse('2026-10-24 17:00:00')->toIso8601String(),
            $data['last_mark']['time'],
        );
    }
}
