@php
    $brand = $tenantBrand ?? $nurseryBrand ?? [];
    $displayName = $brand['display_name'] ?? ($tenantDisplayName ?? $nurseryDisplayName ?? config('app.name'));
    $logoUrl = $brand['logo_url'] ?? ($tenantLogoUrl ?? $nurseryLogoUrl ?? null);
    $variant = $variant ?? 'sidebar';
    $fallbackEmoji = $fallbackEmoji ?? '🏢';
    $tagline = $tagline ?? null;
    $markClass = $markClass ?? 'tenant-brand-mark';
@endphp
<div class="{{ $markClass }} {{ $markClass }}--{{ $variant }}">
    <div class="{{ $markClass }}__logo-wrap" aria-hidden="{{ $logoUrl ? 'false' : 'true' }}">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="" class="{{ $markClass }}__logo" loading="lazy" decoding="async">
        @else
            <span class="{{ $markClass }}__fallback">{{ $fallbackEmoji }}</span>
        @endif
    </div>
    <div class="{{ $markClass }}__text">
        <p class="{{ $markClass }}__name">{{ $displayName }}</p>
        @if($tagline)
            <p class="{{ $markClass }}__tagline">{{ $tagline }}</p>
        @endif
    </div>
</div>
