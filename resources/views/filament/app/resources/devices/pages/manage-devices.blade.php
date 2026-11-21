<x-filament-panels::page>
    {{ $this->table }}

    <x-filament::modal
        id="device-token-modal"
        width="3xl"
        :close-by-clicking-away="false"
        :close-by-escaping="false"
        :close-button="false">
        <x-slot name="heading">
            {{ __('devices.token_heading') }}
        </x-slot>

        @include('filament.app.resources.devices.token-modal', [
        'token' => $generatedToken,
        ])

        <x-slot name="footerActions">
            <x-filament::button wire:click="closeTokenModal" color="primary">
                {{ __('actions.close') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>