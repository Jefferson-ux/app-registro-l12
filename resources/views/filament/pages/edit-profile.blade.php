@php
$pageComponent = static::isSimple() ? 'filament-panels::page.simple' : 'filament-panels::page';
@endphp

<x-dynamic-component :component="$pageComponent">
    <style>
        .profile-page {
            --profile-border: #e5e7eb;
            --profile-label: #111827;
            --profile-text: #374151;
            --profile-field-background: #ffffff;
            --profile-field-border: #d1d5db;
            --profile-error: #dc2626;
        }

        .dark .profile-page {
            --profile-border: #3f3f46;
            --profile-label: #ffffff;
            --profile-text: #d4d4d8;
            --profile-field-background: #18181b;
            --profile-field-border: #3f3f46;
            --profile-error: #f87171;
        }

        .profile-page__details {
            margin: 0;
        }

        .profile-page__row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--profile-border);
        }

        .profile-page__row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .profile-page__field {
            min-width: 0;
            flex: 1;
        }

        .profile-page__label {
            color: var(--profile-label);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .profile-page__value {
            margin-top: 0.25rem;
            color: var(--profile-text);
            font-size: 0.875rem;
        }

        .profile-page__email {
            overflow-wrap: anywhere;
        }

        .profile-page__edit-controls {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .profile-page__input-wrap {
            min-width: 0;
            flex: 1;
        }

        .profile-page__input {
            display: block;
            box-sizing: border-box;
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid var(--profile-field-border);
            border-radius: 0.5rem;
            background: var(--profile-field-background);
            color: var(--profile-label);
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
        }

        .profile-page__input:focus {
            border-color: var(--primary-500, #2ec411);
            outline: 2px solid transparent;
            box-shadow: 0 0 0 2px var(--primary-500, #2ec411);
        }

        .profile-page__error {
            display: block;
            margin-top: 0.25rem;
            color: var(--profile-error);
            font-size: 0.875rem;
        }

        .profile-page__security {
            margin-top: 1.5rem;
        }

        .profile-page__otp-fields {
            display: flex;
            max-width: 28rem;
            flex-direction: column;
            gap: 1rem;
        }

        .profile-page__otp-label {
            display: block;
            color: var(--profile-label);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .profile-page__otp-input {
            margin-top: 0.25rem;
        }

        .profile-page__password-wrap {
            position: relative;
        }

        .profile-page__password-input {
            padding-right: 2.75rem;
        }

        .profile-page__password-toggle {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            display: grid;
            width: 1.25rem;
            height: 1.25rem;
            padding: 0;
            transform: translateY(-50%);
            place-items: center;
            border: 0;
            background: transparent;
            color: var(--profile-text);
            cursor: pointer;
        }

        .profile-page__password-toggle:focus-visible {
            border-radius: 0.25rem;
            outline: 2px solid var(--primary-500, #2ec411);
            outline-offset: 2px;
        }

        .profile-page__otp-code {
            text-align: center;
            font-family: monospace;
            font-size: 1.125rem;
            letter-spacing: 0.25em;
        }

        .profile-page__otp-notice {
            padding: 0.75rem;
            border-radius: 0.5rem;
            background: #eff9ed;
            color: #166534;
        }

        .dark .profile-page__otp-notice {
            background: #27272a;
            color: #d4d4d8;
        }

        .profile-page__actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        @media (max-width: 480px) {
            .profile-page__edit-controls {
                flex-wrap: wrap;
            }

            .profile-page__edit-controls .profile-page__input-wrap {
                flex-basis: 100%;
            }

            .profile-page__actions {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="profile-page">
        <x-filament::button :href="url('/app')" icon="heroicon-o-arrow-left" color="gray" style="margin: 20px;" class="mb-4">
            {{ __('Volver al panel') }}
        </x-filament::button>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('Perfil') }}
            </x-slot>

            <dl class="profile-page__details">
                <div class="profile-page__row">
                    <div class="profile-page__field">
                        <dt class="profile-page__label">{{ __('Nombre') }}</dt>

                        @if ($isEditingName)
                        <dd class="profile-page__edit-controls">
                            <div class="profile-page__input-wrap">
                                <input
                                    id="profile-name"
                                    type="text"
                                    wire:model="name"
                                    minlength="2"
                                    maxlength="150"
                                    autocomplete="name"
                                    required
                                    autofocus
                                    class="profile-page__input" />
                                @error('name')
                                <span class="profile-page__error">{{ $message }}</span>
                                @enderror
                            </div>

                            <x-filament::icon-button
                                icon="heroicon-o-check"
                                color="success"
                                :label="__('Guardar nombre')"
                                :tooltip="__('Guardar nombre')"
                                wire:click="saveName" />
                            <x-filament::icon-button
                                icon="heroicon-o-x-mark"
                                color="gray"
                                :label="__('Cancelar edición')"
                                :tooltip="__('Cancelar edición')"
                                wire:click="cancelNameEdit" />
                        </dd>
                        @else
                        <dd class="profile-page__value">{{ $name }}</dd>
                        @endif
                    </div>

                    @unless ($isEditingName)
                    <x-filament::icon-button
                        icon="heroicon-o-pencil-square"
                        color="gray"
                        :label="__('Editar nombre')"
                        :tooltip="__('Editar nombre')"
                        wire:click="startNameEdit" />
                    @endunless
                </div>

                <div class="profile-page__row">
                    <div class="profile-page__field">
                        <dt class="profile-page__label">{{ __('Correo electrónico') }}</dt>
                        <dd class="profile-page__value profile-page__email">{{ $this->getUser()->email }}</dd>
                    </div>
                </div>
            </dl>
        </x-filament::section>

        <x-filament::section class="profile-page__security">
            <x-slot name="heading">
                {{ __('Seguridad y Cambio de Contraseña') }}
            </x-slot>

            <x-slot name="description">
                {{ __('Para cambiar tu contraseña debes verificar tu identidad mediante un código enviado a tu correo.') }}
            </x-slot>

            @if (! $otpSent)
            <div class="profile-page__otp-fields">
                <div>
                    <label for="current-password" class="profile-page__otp-label">{{ __('Contraseña Actual') }}</label>
                    <div class="profile-page__password-wrap" x-data="{ visible: false }">
                        <input id="current-password" x-bind:type="visible ? 'text' : 'password'" wire:model="current_password" class="profile-page__input profile-page__otp-input profile-page__password-input" autocomplete="current-password" required />
                        <button type="button" class="profile-page__password-toggle" x-on:click="visible = ! visible" x-bind:aria-label="visible ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))" x-bind:aria-pressed="visible">
                            <x-filament::icon icon="heroicon-o-eye" x-show="! visible" />
                            <x-filament::icon icon="heroicon-o-eye-slash" x-show="visible" />
                        </button>
                    </div>
                    @error('current_password') <span class="profile-page__error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="new-password" class="profile-page__otp-label">{{ __('Nueva Contraseña') }}</label>
                    <div class="profile-page__password-wrap" x-data="{ visible: false }">
                        <input id="new-password" x-bind:type="visible ? 'text' : 'password'" wire:model="new_password" class="profile-page__input profile-page__otp-input profile-page__password-input" autocomplete="new-password" required />
                        <button type="button" class="profile-page__password-toggle" x-on:click="visible = ! visible" x-bind:aria-label="visible ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))" x-bind:aria-pressed="visible">
                            <x-filament::icon icon="heroicon-o-eye" x-show="! visible" />
                            <x-filament::icon icon="heroicon-o-eye-slash" x-show="visible" />
                        </button>
                    </div>
                    @error('new_password') <span class="profile-page__error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="new-password-confirmation" class="profile-page__otp-label">{{ __('Confirmar Nueva Contraseña') }}</label>
                    <div class="profile-page__password-wrap" x-data="{ visible: false }">
                        <input id="new-password-confirmation" x-bind:type="visible ? 'text' : 'password'" wire:model="new_password_confirmation" class="profile-page__input profile-page__otp-input profile-page__password-input" autocomplete="new-password" required />
                        <button type="button" class="profile-page__password-toggle" x-on:click="visible = ! visible" x-bind:aria-label="visible ? @js(__('Ocultar contraseña')) : @js(__('Mostrar contraseña'))" x-bind:aria-pressed="visible">
                            <x-filament::icon icon="heroicon-o-eye" x-show="! visible" />
                            <x-filament::icon icon="heroicon-o-eye-slash" x-show="visible" />
                        </button>
                    </div>
                    @error('new_password_confirmation') <span class="profile-page__error">{{ $message }}</span> @enderror
                </div>

                <x-filament::button wire:click="requestPasswordChangeOtp" type="button">
                    {{ __('Solicitar Código de Verificación') }}
                </x-filament::button>
            </div>
            @else
            <div class="profile-page__otp-fields">
                <div class="profile-page__otp-notice">
                    <p>{{ __('Ingresa el código de 6 dígitos enviado a tu correo electrónico.') }}</p>
                </div>

                <div>
                    <label class="profile-page__otp-label">{{ __('Código OTP') }}</label>
                    <input type="text" wire:model="otp_code" maxlength="6" class="profile-page__input profile-page__otp-input profile-page__otp-code" placeholder="123456" required />
                    @error('otp_code') <span class="profile-page__error">{{ $message }}</span> @enderror
                </div>

                <div class="profile-page__actions">
                    <x-filament::button wire:click="confirmPasswordChange" type="button" color="success">
                        {{ __('Confirmar y Cambiar Contraseña') }}
                    </x-filament::button>

                    <x-filament::button wire:click="$set('otpSent', false)" type="button" color="gray">
                        {{ __('Cancelar') }}
                    </x-filament::button>
                </div>
            </div>
            @endif
        </x-filament::section>
    </div>
</x-dynamic-component>