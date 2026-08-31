<?php

namespace App\Filament\Resources\EmployeeSchedules\Pages;

use App\Filament\Resources\EmployeeSchedules\EmployeeScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmployeeSchedules extends ManageRecords
{
    protected static string $resource = EmployeeScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
