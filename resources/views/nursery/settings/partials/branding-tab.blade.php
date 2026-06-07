@include('tenant.settings.partials.branding-tab', [
    'branding' => $settings->branding(),
    'hintPrefix' => 'nursery',
    'entityLabel' => 'الحضانة',
    'accent' => 'orange',
    'submitRoute' => route('nursery.settings.branding.update'),
    'canManage' => $canManage,
    'portalUrl' => $portalUrl ?? null,
    'portalPathHint' => '/nursery-portal/'.($tenantSlug ?? 'slug'),
    'displayNameValue' => old('display_name', $brandingDisplayName ?? null),
    'displayNamePlaceholder' => $settings->nursery_name,
    'displayNameHelp' => 'إن تُرك فارغاً يُستخدم «اسم الحضانة» من إعدادات الحساب.',
    'previewEmoji' => '🧸',
])
