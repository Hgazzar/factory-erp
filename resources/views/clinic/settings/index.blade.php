@extends('layouts.clinic')

@section('title', 'إعدادات العيادة')

@section('content')
<div class="clinic-page-header">
    <div>
        <h1 class="clinic-page-title">إعدادات العيادة</h1>
        <p class="clinic-page-subtitle">
            <x-info field="clinic.settings_intro" />
            الهوية البصرية تظهر في بوابة المرضى ولوحة العيادة.
        </p>
    </div>
</div>

@if(session('success'))
    <div class="clinic-card px-4 py-3 text-sm text-emerald-800 bg-emerald-50 mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="clinic-card px-4 py-3 text-sm text-red-800 bg-red-50 mb-4">{{ session('error') }}</div>
@endif

@include('tenant.settings.partials.branding-tab', [
    'branding' => $branding,
    'hintPrefix' => 'clinic',
    'entityLabel' => 'العيادة',
    'accent' => 'teal',
    'submitRoute' => route('clinic.settings.branding.update'),
    'canManage' => $canManage,
    'portalUrl' => $portalUrl,
    'portalPathHint' => $portalPathHint,
    'displayNameValue' => $displayNameValue ?? '',
    'displayNamePlaceholder' => $branding['fallback_name'] ?? '',
    'displayNameHelp' => 'إن تُرك فارغاً يُستخدم اسم الشركة من إعدادات النظام.',
    'previewEmoji' => '🏥',
])
@endsection
