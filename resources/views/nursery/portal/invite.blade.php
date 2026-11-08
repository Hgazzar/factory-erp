@extends('layouts.nursery-portal')

@section('title', 'دخول البوابة')

@section('content')
<div class="np-card">
    <h2 class="text-lg font-bold text-teal-950 mb-1">مرحباً بك</h2>
    <p class="text-sm text-teal-800/80 mb-4">
        أدخل رقم الجوال المسجّل لدى الحضانة لفتح البوابة.
        <x-info field="nursery.portal_login_phone" />
    </p>

    <form method="POST" action="{{ route('nursery.portal.invite.login', ['tenant_slug' => $tenantSlug, 'token' => $inviteToken]) }}" class="space-y-4">
        @csrf
        <div>
            <label class="np-label" for="phone">رقم الجوال <x-info field="nursery.portal_login_phone" /></label>
            <input type="tel" name="phone" id="phone" class="np-input" dir="ltr"
                   value="{{ old('phone') }}" required autocomplete="tel"
                   placeholder="05xxxxxxxx" autofocus>
            @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="np-btn np-btn-primary">دخول البوابة</button>
    </form>
</div>
@endsection
