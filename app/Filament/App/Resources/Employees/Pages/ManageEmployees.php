<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Filament\App\Resources\Employees\Widgets\EmployeeLegendWidget;
use App\Actions\Employee\ProcessFaceCaptureAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmployees extends ManageRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn(array $data) => app(ProcessFaceCaptureAction::class)->execute($data)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeeLegendWidget::class,
        ];
    }
}
