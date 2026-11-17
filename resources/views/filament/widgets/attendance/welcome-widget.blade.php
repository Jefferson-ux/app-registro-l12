<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display: flex; align-items: center; gap: 20px;">
            <x-filament-panels::avatar.user size="lg" :user="auth()->user()" />

            <div>
                <span style="font-weight: 700; font-size: 1.5em;">{{ __('messages.welcome.greeting') }}</span>&nbsp;
                <span style="color: #707c83;  font-size: 1.3em">{{ filament()->getUserName(auth()->user()) }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>