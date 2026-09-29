<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeStatus extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "employee status" => $this->employment_status,
        ];
    }
}
