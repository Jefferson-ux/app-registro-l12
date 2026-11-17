<x-filament::modal
    id="logout-confirm"
    width="md"
    alignment="center"
    icon="heroicon-o-arrow-right-on-rectangle"
    icon-color="danger">
    <x-slot name="heading">
        {{ __('messages.logout_modal.heading') }}
    </x-slot>

    <x-slot name="description">
        {{ __('messages.logout_modal.description') }}
    </x-slot>

    <x-slot name="footer">
        <div style="display: flex; gap: 12px; justify-content: center;">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'logout-confirm' })">
                {{ __('messages.logout_modal.cancel') }}
            </x-filament::button>

            <form method="POST" action="{{ filament()->getLogoutUrl() }}" data-logout-confirmed="1">
                @csrf
                <x-filament::button type="submit" color="danger">
                    {{ __('messages.logout_modal.confirm') }}
                </x-filament::button>
            </form>
        </div>
    </x-slot>
</x-filament::modal>

<script>
    if (!window.logoutGuardInstalled) {
        window.logoutGuardInstalled = true;

        document.addEventListener('submit', function(event) {
            const form = event.target;

            if (form.dataset.logoutConfirmed) {
                return;
            }

            const confirmForm = document.querySelector('form[data-logout-confirmed]');

            if (!confirmForm) {
                return;
            }

            const logoutPath = new URL(confirmForm.action, window.location.origin).pathname;
            const formPath = new URL(form.action, window.location.origin).pathname;

            if (formPath !== logoutPath) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            window.dispatchEvent(
                new CustomEvent('open-modal', {
                    detail: {
                        id: 'logout-confirm'
                    }
                })
            );
        }, true);
    }
</script>