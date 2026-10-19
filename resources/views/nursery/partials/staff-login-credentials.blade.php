@if(session('staff_login_credentials'))
    @php
        $creds = session('staff_login_credentials');
        $credEmail = (string) ($creds['email'] ?? '');
        $credPassword = (string) ($creds['password'] ?? '');
        $credName = (string) ($creds['employee_name'] ?? '');
        $credLoginUrl = (string) ($creds['login_url'] ?? route('nursery.login'));
    @endphp
    <div class="nursery-card p-4 border-amber-200 bg-amber-50 space-y-3" role="status" data-staff-login-credentials>
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <p class="text-sm font-extrabold text-amber-950">
                    بيانات دخول مؤقتة
                    <x-info field="nursery.staff_login_credentials" />
                </p>
                @if($credName !== '')
                    <p class="text-xs text-amber-900/80 mt-0.5">للموظف: {{ $credName }}</p>
                @endif
                <p class="text-xs text-amber-900/80 mt-1">لا تُحفظ كلمة المرور في النظام بعد هذه الشاشة — انسخها الآن أو أعد توليدها لاحقاً من تعديل الموظف.</p>
            </div>
        </div>
        <dl class="grid gap-2 sm:grid-cols-2 text-sm">
            <div class="rounded-lg bg-white/80 border border-amber-100 px-3 py-2">
                <dt class="text-xs text-amber-800/80">البريد</dt>
                <dd class="font-mono font-semibold text-teal-950 break-all" dir="ltr">{{ $credEmail }}</dd>
            </div>
            <div class="rounded-lg bg-white/80 border border-amber-100 px-3 py-2">
                <dt class="text-xs text-amber-800/80">كلمة المرور المؤقتة</dt>
                <dd class="font-mono font-semibold text-teal-950 tracking-wide" dir="ltr" id="staff-temp-password">{{ $credPassword }}</dd>
            </div>
            <div class="rounded-lg bg-white/80 border border-amber-100 px-3 py-2 sm:col-span-2">
                <dt class="text-xs text-amber-800/80">رابط الدخول</dt>
                <dd class="font-mono text-teal-800 break-all" dir="ltr">
                    <a href="{{ $credLoginUrl }}" class="underline" target="_blank" rel="noopener">{{ $credLoginUrl }}</a>
                </dd>
            </div>
        </dl>
        <div class="flex flex-wrap gap-2">
            <button type="button"
                    class="nursery-btn nursery-btn-primary text-xs py-1.5"
                    data-copy-staff-credentials
                    data-email="{{ $credEmail }}"
                    data-password="{{ $credPassword }}">
                نسخ البريد وكلمة المرور
            </button>
        </div>
    </div>
    @push('scripts')
    <script>
    (function () {
        const btn = document.querySelector('[data-copy-staff-credentials]');
        if (!btn) return;
        btn.addEventListener('click', async function () {
            const text = (btn.getAttribute('data-email') || '') + '\n' + (btn.getAttribute('data-password') || '');
            try {
                await navigator.clipboard.writeText(text);
                const old = btn.textContent;
                btn.textContent = 'تم النسخ';
                setTimeout(function () { btn.textContent = old; }, 2000);
            } catch (e) {
                const el = document.getElementById('staff-temp-password');
                if (el) {
                    const range = document.createRange();
                    range.selectNodeContents(el);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }
        });
    })();
    </script>
    @endpush
@endif
