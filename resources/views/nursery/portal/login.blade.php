@extends('layouts.nursery-portal')

@section('title', 'تسجيل الدخول')

@section('content')
@php
    $otpStep = session('otp_sent') ? 'verify' : 'phone';
@endphp
<div class="np-card">
    <h2 class="text-lg font-bold text-orange-950 mb-1">مرحباً بك</h2>
    <p class="text-sm text-orange-800/80 mb-4">
        سجّل دخولك برقم الجوال المسجّل لدى الحضانة.
        <x-info field="nursery.portal_login_phone" />
    </p>

    @if($otpLogOnly)
        <p class="text-xs bg-orange-50 border border-orange-100 rounded-lg px-3 py-2 mb-4 text-orange-800">
            بيئة التطوير: رمز OTP ثابت <strong dir="ltr">{{ config('nursery.portal.dev_otp_code', '123456') }}</strong> — يُسجَّل أيضاً في الـ log.
        </p>
    @endif

    @if($otpStep === 'phone')
        <form method="POST" action="{{ route('nursery.portal.otp.request', ['tenant_slug' => $tenantSlug]) }}" class="space-y-4">
            @csrf
            <div>
                <label class="np-label" for="phone">رقم الجوال <x-info field="nursery.portal_login_phone" /></label>
                <input type="tel" name="phone" id="phone" class="np-input" dir="ltr"
                       value="{{ old('phone') }}" required autocomplete="tel"
                       placeholder="05xxxxxxxx">
                @error('phone')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="np-btn np-btn-primary">إرسال رمز التحقق</button>
        </form>
    @else
        <form method="POST" action="{{ route('nursery.portal.otp.verify', ['tenant_slug' => $tenantSlug]) }}" class="space-y-4">
            @csrf
            <div>
                <label class="np-label" for="phone_verify">رقم الجوال</label>
                <input type="tel" name="phone" id="phone_verify" class="np-input" dir="ltr"
                       value="{{ old('phone') }}" required readonly>
            </div>
            <div>
                <label class="np-label" for="otp">رمز التحقق <x-info field="nursery.portal_login_otp" /></label>
                <input type="text" name="otp" id="otp" class="np-input text-center tracking-widest" dir="ltr"
                       maxlength="8" inputmode="numeric" autocomplete="one-time-code" required
                       placeholder="••••••" autofocus>
                @error('otp')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="np-btn np-btn-primary">تسجيل الدخول</button>
            <a href="{{ route('nursery.portal.login', ['tenant_slug' => $tenantSlug]) }}"
               class="np-btn np-btn-soft mt-2">تغيير الرقم</a>
        </form>
    @endif
</div>
@endsection
