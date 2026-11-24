<?php

namespace Tests\Unit;

use App\Http\Requests\Api\FaceMarkRequest;
use App\Http\Requests\Api\MarkRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AttendanceRequestMetadataValidationTest extends TestCase
{
    public function test_mark_request_accepts_optional_location_and_device_metadata(): void
    {
        $validator = Validator::make([
            'employee_code' => 'EMP-001',
            'request_id' => '1f9193d9-fdb1-43af-99d8-6ab1ef1b03d1',
            'latitude' => 12.345,
            'longitude' => -76.543,
            'device_identifier' => 'kiosk-01',
        ], (new MarkRequest)->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_face_mark_request_accepts_optional_location_and_device_metadata(): void
    {
        $validator = Validator::make([
            'face_descriptor' => array_fill(0, 128, 0.1),
            'request_id' => 'request-1',
            'latitude' => 12.345,
            'longitude' => -76.543,
            'device_identifier' => 'kiosk-01',
        ], (new FaceMarkRequest)->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_location_coordinates_must_be_sent_together_and_within_range(): void
    {
        $rules = (new MarkRequest)->rules();
        $baseData = [
            'employee_code' => 'EMP-001',
            'request_id' => '1f9193d9-fdb1-43af-99d8-6ab1ef1b03d1',
        ];

        $missingLongitude = Validator::make($baseData + ['latitude' => 12.345], $rules);
        $outOfRangeLatitude = Validator::make($baseData + ['latitude' => 91, 'longitude' => 0], $rules);
        $outOfRangeLongitude = Validator::make($baseData + ['latitude' => 0, 'longitude' => 181], $rules);

        $this->assertFalse($missingLongitude->passes());
        $this->assertFalse($outOfRangeLatitude->passes());
        $this->assertFalse($outOfRangeLongitude->passes());
    }

    public function test_kiosk_requests_cannot_submit_notes(): void
    {
        $validator = Validator::make([
            'employee_code' => 'EMP-001',
            'request_id' => '1f9193d9-fdb1-43af-99d8-6ab1ef1b03d1',
            'notes' => 'Nota desde el kiosco',
        ], (new MarkRequest)->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('notes', $validator->errors()->toArray());
    }
}
