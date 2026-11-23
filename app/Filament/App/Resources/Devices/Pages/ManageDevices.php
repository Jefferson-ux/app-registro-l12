<?php

namespace App\Filament\App\Resources\Devices\Pages;

use App\Filament\App\Resources\Devices\DeviceResource;
use App\Models\Device;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDevices extends ManageRecords
{
    protected static string $resource = DeviceResource::class;

    protected string $view = 'filament.app.resources.devices.pages.manage-devices';

    public ?string $generatedToken = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false)
                ->modalHeading(__('devices.create_heading'))
                ->modalSubmitActionLabel(__('devices.create_button'))
                ->after(function (Device $record): void {
                    $this->showGeneratedToken($record->issueToken());
                }),
        ];
    }

    public function showGeneratedToken(string $token): void
    {
        $this->generatedToken = $token;

        $this->dispatch('device-token-generated', token: $token);
        $this->dispatch('open-modal', id: 'device-token-modal');
    }

    public function closeTokenModal(): void
    {
        $this->generatedToken = null;

        $this->dispatch('device-token-generated', token: '');
        $this->dispatch('close-modal', id: 'device-token-modal');
    }
}
