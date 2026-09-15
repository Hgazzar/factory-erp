@extends('layouts.nursery')

@section('title', 'حساب الدخول')
@section('topbar_subtitle', 'إيميل تسجيل الدخول وكلمة المرور')

@section('content')
<div class="w-full max-w-2xl space-y-5" dir="rtl">
    <div>
        <h1 class="text-2xl font-bold text-teal-950">حساب الدخول</h1>
        <p class="text-sm text-teal-800/70 mt-1">إدارة اسم العرض، إيميل تسجيل الدخول، وكلمة المرور.</p>
    </div>

    {{-- معلومات الحساب --}}
    <section class="nursery-card p-5 space-y-4">
        <div class="border-b border-teal-100 pb-3">
            <h2 class="text-lg font-bold text-teal-950">بيانات الحساب</h2>
            <p class="text-sm text-teal-800/70 mt-1">الاسم الظاهر وإيميل تسجيل الدخول.</p>
        </div>

        <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('patch')

            <div>
                <label for="name" class="block text-sm font-semibold text-teal-950 mb-1">
                    الاسم الظاهر <x-info field="nursery.profile_display_name" />
                </label>
                <input id="name" name="name" type="text" required
                       value="{{ old('name', $user->name) }}"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm"
                       autocomplete="name">
                <p class="text-xs text-teal-700/70 mt-1">يظهر في القائمة — ليس بريد تسجيل الدخول.</p>
                @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-teal-950 mb-1">
                    إيميل تسجيل الدخول <x-info field="nursery.profile_login_email" />
                </label>
                <input id="email" name="email" type="email" required dir="ltr"
                       value="{{ old('email', $user->email) }}"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm font-mono"
                       autocomplete="username">
                <p class="text-xs text-teal-700/70 mt-1">بهذا البريد تدخل للنظام. بعد تغييره سجّل الدخول بالإيميل الجديد.</p>
                @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ البيانات</button>
                @if (session('status') === 'profile-updated')
                    <span class="text-sm text-teal-700 font-medium">تم الحفظ.</span>
                @endif
            </div>
        </form>
    </section>

    {{-- كلمة المرور --}}
    <section class="nursery-card p-5 space-y-4">
        <div class="border-b border-teal-100 pb-3">
            <h2 class="text-lg font-bold text-teal-950">تغيير كلمة المرور</h2>
            <p class="text-sm text-teal-800/70 mt-1">استخدم كلمة مرور قوية لا تشاركها مع أحد.</p>
        </div>

        <form method="post" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('put')

            <div>
                <label for="update_password_current_password" class="block text-sm font-semibold text-teal-950 mb-1">
                    كلمة المرور الحالية <x-info field="nursery.profile_current_password" />
                </label>
                <input id="update_password_current_password" name="current_password" type="password"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm" dir="ltr"
                       autocomplete="current-password">
                @error('current_password', 'updatePassword')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="update_password_password" class="block text-sm font-semibold text-teal-950 mb-1">
                    كلمة المرور الجديدة <x-info field="nursery.profile_new_password" />
                </label>
                <input id="update_password_password" name="password" type="password"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm" dir="ltr"
                       autocomplete="new-password">
                @error('password', 'updatePassword')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="update_password_password_confirmation" class="block text-sm font-semibold text-teal-950 mb-1">
                    تأكيد كلمة المرور الجديدة
                </label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password"
                       class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm" dir="ltr"
                       autocomplete="new-password">
                @error('password_confirmation', 'updatePassword')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-1">
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ كلمة المرور</button>
                @if (session('status') === 'password-updated')
                    <span class="text-sm text-teal-700 font-medium">تم الحفظ.</span>
                @endif
            </div>
        </form>
    </section>
</div>
@endsection
