@php
    /** @var \App\Models\Employee $employee */
    $hasLogin = filled($employee->linked_user_id);
    $hasEmail = filled($employee->email);
@endphp

<section class="nursery-card p-4 space-y-3 border-teal-200 bg-teal-50/40" id="staff-login-account-panel">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="text-base font-extrabold text-teal-950">
                حساب دخول النظام
                <x-info field="nursery.staff_login_account_panel" />
            </h2>
            <p class="text-sm text-teal-800/80 mt-1">
                @if($hasLogin)
                    للموظف حساب دخول مرتبط.
                    @if($hasEmail)
                        البريد: <span class="font-mono font-semibold" dir="ltr">{{ $employee->email }}</span>
                    @endif
                @elseif($hasEmail)
                    يوجد بريد إلكتروني لكن لا يوجد حساب دخول بعد — استخدم الزر البرتقالي أسفل النموذج: «حفظ وإنشاء حساب دخول».
                @else
                    أضف بريداً إلكترونياً أولاً، ثم اضغط «حفظ وإنشاء حساب دخول» أسفل الصفحة.
                @endif
            </p>
        </div>
        @if($hasLogin)
            <span class="nursery-status-pill nursery-status-pill--success">حساب مفعّل</span>
        @else
            <span class="nursery-status-pill nursery-status-pill--muted">بدون حساب</span>
        @endif
    </div>

    @if($hasLogin)
        <form method="post" action="{{ route('nursery.staff.reset-login-password', $employee) }}"
              onsubmit="return confirm('سيتم إنشاء كلمة مرور مؤقتة جديدة وإبطال القديمة. متابعة؟')">
            @csrf
            <button type="submit" class="nursery-btn nursery-btn-primary">
                إعادة كلمة مرور الدخول
            </button>
            <p class="text-xs text-teal-700/80 mt-2">
                <x-info field="nursery.staff_reset_login_password" />
                بعد الضغط تظهر بطاقة صفراء أعلى الصفحة بكلمة المرور الجديدة — انسخها فوراً.
            </p>
        </form>
    @endif
</section>
