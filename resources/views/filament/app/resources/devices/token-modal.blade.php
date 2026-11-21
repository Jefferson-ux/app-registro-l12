@props(['token' => null])

<div
    x-data="{
        token: @js($token ?? ''),
        copied: false,
        setToken(val) {
            this.token = val || '';
            this.copied = false;
        },
        async copyToken() {
            if (!this.token) return;
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(this.token);
                } else {
                    const ta = document.createElement('textarea');
                    ta.value = this.token;
                    ta.setAttribute('readonly', '');
                    ta.style.cssText = 'position:fixed;left:-9999px;';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            } catch (e) {
                console.error('Error al copiar el token:', e);
            }
        }
    }"
    x-on:device-token-generated.window="setToken($event.detail?.token ?? $event.detail?.[0]?.token ?? '')"
    class="kiosk-token-wrap">
    <style>
        .kiosk-token-wrap {
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 100%;
        }

        .kiosk-token-warn {
            background: #f5e6c8;
            border: 1px solid #d6b47c;
            color: #4a3210;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
        }

        .kiosk-token-box {
            background: #3730a3;
            border: 1px solid #6366f1;
            padding: 16px;
            border-radius: 12px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
        }

        .kiosk-token-input {
            flex: 1 1 240px;
            min-width: 0;
            background: #eef2ff;
            color: #1e1b4b;
            border: 1px solid #a5b4fc;
            border-radius: 8px;
            padding: 10px 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 14px;
            font-weight: 700;
            outline: none;
        }

        .kiosk-token-btn {
            background: #6366f1;
            color: #fff;
            border: 1px solid #a5b4fc;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            cursor: pointer;
        }

        .kiosk-token-btn.is-copied {
            background: #059669;
            border-color: #6ee7b7;
        }

        @media (max-width: 640px) {
            .kiosk-token-btn {
                width: 100%;
            }
        }
    </style>

    <div class="kiosk-token-warn">{{ __('devices.token_warning') }}</div>

    <div class="kiosk-token-box">
        <input type="text" readonly class="kiosk-token-input" :value="token" x-on:click="$el.select()">
        <button type="button" class="kiosk-token-btn" :class="{ 'is-copied': copied }" x-on:click="copyToken()" x-text="copied ? @js(__('devices.copied')) : @js(__('actions.copy'))"></button>
    </div>
</div>