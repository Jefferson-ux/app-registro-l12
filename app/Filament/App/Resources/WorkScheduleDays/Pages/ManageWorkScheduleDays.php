<?php

namespace App\Filament\App\Resources\WorkScheduleDays\Pages;

use App\Filament\App\Resources\WorkScheduleDays\WorkScheduleDayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWorkScheduleDays extends ManageRecords
{
    protected static string $resource = WorkScheduleDayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
