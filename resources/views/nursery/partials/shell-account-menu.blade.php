{{-- قائمة الحساب داخل شِل الحضانة (بدل الشريط العام لمستخدمي الحضانة) --}}
@auth
    <div class="nursery-account-menu {{ $compact ?? false ? 'nursery-account-menu--compact' : '' }}" data-nursery-account-menu>
        <div class="nursery-account-menu__who min-w-0">
            <span class="nursery-account-menu__avatar" aria-hidden="true">{{ strtoupper(mb_substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
            <div class="nursery-account-menu__meta min-w-0">
                <p class="nursery-account-menu__name truncate">{{ Auth::user()->name }}</p>
                <a href="{{ route('profile.edit') }}" class="nursery-account-menu__link">حساب الدخول</a>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="m-0 shrink-0">
            @csrf
            <button type="submit" class="nursery-account-menu__logout" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                </svg>
                <span class="nursery-account-menu__logout-label">خروج</span>
            </button>
        </form>
    </div>
@endauth
