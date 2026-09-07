<?php

namespace App\Filament\App\Resources\AttendanceIncidents\Pages;

use App\Filament\App\Resources\AttendanceIncidents\AttendanceIncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendanceIncidents extends ManageRecords
{
    protected static string $resource = AttendanceIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
