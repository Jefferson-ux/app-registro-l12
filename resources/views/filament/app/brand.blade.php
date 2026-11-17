@php
$user = auth()->user();
$tenant = filament()->getTenant() ?? $user?->tenant ?? $user?->currentTenant;

$logoUrl = $tenant?->logo ? asset('storage/' . $tenant->logo) : null;
$companyName = $tenant?->name ?? 'Panel Empresarial';
@endphp

<div style="display: flex; align-items: center; gap: 10px; padding: 4px 0;">
    @if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" style="height: 45px; width: 45px; object-fit: cover; border-radius: 6px;">
    @endif

    <span style="font-size: 1.4rem; font-weight: 600; color: inherit; letter-spacing: -0.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
        {{ $companyName }}
    </span>
</div>