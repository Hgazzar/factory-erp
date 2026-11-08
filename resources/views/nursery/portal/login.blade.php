@extends('layouts.nursery-portal')

@section('title', 'تسجيل الدخول')

@section('content')
<div class="np-card">
    <h2 class="text-lg font-bold text-teal-950 mb-1">مرحباً بك</h2>
    <p class="text-sm text-teal-800/80 mb-4">
        الدخول يتم عبر <strong>رابط الدعوة</strong> الذي ترسله الحضانة على واتساب، ثم تأكيد رقم الجوال المسجّل.
        <x-info field="nursery.portal_login_invite" />
    </p>

    <div class="rounded-xl border border-teal-100 bg-teal-50/60 px-3 py-3 text-sm text-teal-900 space-y-2">
        <p class="font-semibold">كيف تدخل؟</p>
        <ol class="list-decimal list-inside space-y-1 text-teal-800/90">
            <li>افتح رابط الدعوة من رسالة واتساب الحضانة.</li>
            <li>أدخل رقم جوالك المسجّل لدى الحضانة.</li>
            <li>ادخل لمتابعة أطفالك.</li>
        </ol>
    </div>
</div>
@endsection
