<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; align-items: center; gap: 14px;">
            <x-filament-panels::avatar.user size="lg" :user="auth()->user()" />

            <div>
                <div style="font-weight: 700;">{{ __('messages.welcome.greeting') }}</div>
                <div style="color: #707c83;">{{ filament()->getUserName(auth()->user()) }}</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>