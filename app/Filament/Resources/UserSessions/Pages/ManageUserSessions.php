<?php

namespace App\Filament\Resources\UserSessions\Pages;

use App\Filament\Resources\UserSessions\UserSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUserSessions extends ManageRecords
{
    protected static string $resource = UserSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
