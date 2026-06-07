@include('tenant.partials.brand-mark', [
    'tenantBrand' => $tenantBrand ?? $nurseryBrand ?? null,
    'variant' => $variant ?? 'sidebar',
    'fallbackEmoji' => '🧸',
    'tagline' => ($variant ?? '') === 'portal' ? 'متابعة أطفالك — حضور، اشتراكات، وتقويم' : null,
    'markClass' => 'nursery-brand-mark',
])
