    @php
        $quickAccessModules = collect([
            ['module' => 'finance', 'label' => niche_module_label('finance'), 'subtitle' => 'لوحة المحاسبة', 'route' => 'finance.dashboard', 'iconBg' => 'rgba(2, 119, 189, 0.2)', 'iconColor' => '#0277bd', 'icon' => '<path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.471c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4z"/>'],
            ['module' => 'inventory', 'label' => niche_module_label('inventory'), 'subtitle' => niche_label('modules.warehouses', 'لوحة المخزون'), 'route' => 'inventory.dashboard', 'iconBg' => 'rgba(245, 158, 11, 0.25)', 'iconColor' => '#ea580c', 'icon' => '<path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113z"/><path d="M15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6z"/>'],
            ['module' => 'sales', 'label' => niche_module_label('sales'), 'subtitle' => 'لوحة المبيعات', 'route' => 'sales.dashboard', 'iconBg' => 'rgba(46, 125, 50, 0.2)', 'iconColor' => '#2e7d32', 'icon' => '<path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2z"/>'],
            ['module' => 'pos', 'label' => niche_module_label('pos'), 'subtitle' => 'لوحة نقاط البيع', 'route' => 'pos.dashboard', 'iconBg' => 'rgba(220, 38, 38, 0.15)', 'iconColor' => '#dc2626', 'icon' => '<path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2z"/><path d="M4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3zm0 4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7z"/>'],
            ['module' => 'purchases', 'label' => niche_module_label('purchases'), 'subtitle' => 'لوحة المشتريات', 'route' => 'purchases.dashboard', 'iconBg' => 'rgba(124, 58, 237, 0.2)', 'iconColor' => '#7c3aed', 'icon' => '<path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/>'],
            ['module' => 'manufacturing', 'label' => niche_module_label('manufacturing'), 'subtitle' => 'لوحة التحكم', 'route' => 'manufacturing.dashboard', 'iconBg' => 'rgba(59, 130, 246, 0.18)', 'iconColor' => '#1d4ed8', 'icon' => '<path d="M8 1a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V1zm1 0v14h6V1H9zM2 3a1 1 0 0 1 1-1h3v14H3a1 1 0 0 1-1-1V3z"/>'],
            ['module' => 'hr', 'label' => niche_module_label('hr'), 'subtitle' => 'لوحة الموارد البشرية', 'route' => 'hr.dashboard', 'iconBg' => 'rgba(13, 148, 136, 0.18)', 'iconColor' => '#0d9488', 'icon' => '<path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>'],
            ['module' => 'clinic', 'label' => niche_module_label('clinic'), 'subtitle' => 'لوحة العيادة', 'route' => 'clinic.dashboard', 'iconBg' => 'rgba(13, 148, 136, 0.2)', 'iconColor' => '#0d9488', 'icon' => '<path d="M8.5 5.5a.5.5 0 0 0-1 0v3.362l-1.429 2.38a.5.5 0 1 0 .858.515l1.5-2.5A.5.5 0 0 0 8.5 9V5.5z"/><path d="M6.5 0A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>'],
            ['module' => 'fleet', 'label' => niche_module_label('fleet'), 'subtitle' => 'لوحة المناديب', 'route' => 'fleet.dashboard', 'iconBg' => 'rgba(124, 58, 237, 0.2)', 'iconColor' => '#7c3aed', 'icon' => '<path d="M8 16a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm-1.5-2.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0zm3 0a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V7h1.5A1.5 1.5 0 0 1 15 8.5v2.999l-1.5 1.5V13a1 1 0 0 1-1 1h-1v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V14H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V14H1a1 1 0 0 1-1-1v-1.001L0 10.5V8.5A1.5 1.5 0 0 1 1.5 7H3V3.5z"/>'],
        ])->filter(fn ($mod) => in_array($mod['module'], $enabledModules ?? ['core'], true))->values()->all();
    @endphp
    {{-- Top Global Navbar — يُدمَج داخل .flex.flex-col.min-h-screen في التخطيط الأم --}}
    <header class="sticky top-0 z-50 w-full bg-white border-b border-gray-100 shadow-sm h-14 px-6 flex items-center justify-between shrink-0">
            {{-- LEFT (App Switcher + Logo + Breadcrumb) --}}
            <div class="flex items-center gap-4 min-w-0 flex-1">
                @guest
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0 text-gray-600 hover:text-indigo-600 cursor-pointer" title="لوحة التطبيقات">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </a>
                @else
                    {{-- فتح مودال يدوياً بعد bootstrap.bundle (نفس أسلوب استيراد الحسابات) لتفادي تعطيل data-api على السيرفر --}}
                    <button type="button"
                            id="erp-module-launcher-trigger"
                            class="flex shrink-0 items-center justify-center rounded-lg border-0 bg-transparent p-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0"
                            data-erp-module-launcher="1"
                            data-bs-target="#erpModuleLauncherModal"
                            title="الوصول السريع للوحدات"
                            aria-label="فتح قائمة الوحدات">
                        <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    </button>
                @endguest
                <span class="font-semibold text-indigo-900 text-sm tracking-wide shrink-0">{{ config('app.name') }}</span>
                @hasSection('breadcrumb')
                    <nav class="flex items-center gap-2 text-[0.85rem] text-gray-500 min-w-0 overflow-hidden">
                        @yield('breadcrumb')
                    </nav>
                @else
                    <nav class="flex items-center gap-2 text-[0.85rem] text-gray-500">
                        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
                        @isset($header)
                            <span>›</span>
                            <span class="text-indigo-900 font-semibold truncate">{{ $header }}</span>
                        @endisset
                    </nav>
                @endif
            </div>

            {{-- CENTER (Global Search) --}}
            <div class="hidden md:flex flex-1 max-w-md justify-center px-4">
                <form action="{{ route('dashboard') }}" method="GET" class="flex h-9 w-72 max-w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="ابحث عن عميل، فاتورة، صنف..." class="flex-1 text-sm outline-none bg-transparent border-0 p-0 placeholder-gray-400">
                </form>
            </div>

            {{-- RIGHT (Notifications + Quick Apps + User) --}}
            <div class="flex items-center gap-5 shrink-0">
                @auth
                    @php
                        $unreadCount = Auth::user()->unreadNotifications()->count();
                        $recentNotifications = Auth::user()->notifications()->latest()->limit(8)->get();
                    @endphp
                    {{-- التنبيهات: Bootstrap dropdown (موثوق مع Livewire/Filament على السيرفر) — نفس التنسيق السابق --}}
                    <div class="dropdown">
                        <button type="button"
                                id="navbarNotificationsDropdown"
                                class="relative border-0 bg-transparent p-1 text-gray-500 hover:text-indigo-600 rounded focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0"
                                data-bs-toggle="dropdown"
                                data-bs-display="dynamic"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-label="التنبيهات">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            @if($unreadCount > 0)
                                <span class="absolute -top-1 -left-1 inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-600 text-white text-[10px] font-semibold leading-none">
                                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                                </span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-0 mt-2 w-80 max-w-[calc(100vw-2rem)] rounded-xl bg-white border border-gray-200 shadow-lg overflow-hidden"
                             style="font-family: 'Cairo', sans-serif; direction: rtl; text-align: right;"
                             aria-labelledby="navbarNotificationsDropdown">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-900">التنبيهات</p>
                                <span class="text-xs text-gray-500">{{ $unreadCount }} غير مقروء</span>
                            </div>
                            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                                @forelse($recentNotifications as $notification)
                                    @php $data = $notification->data; $category = $data['category'] ?? null; @endphp
                                    <div class="px-4 py-3 flex items-start gap-3 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/40' }}">
                                        <div class="mt-1">
                                            @if($category === 'commissions')
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-50 text-blue-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 8 8A8.009 8.009 0 0 0 8 0z"/></svg>
                                                </span>
                                            @elseif($category === 'contracts')
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-50 text-amber-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M3 2a1 1 0 0 0-1 1v10.5a.5.5 0 0 0 .757.429L6 12.101l3.243 1.828A.5.5 0 0 0 10 13.5V3a1 1 0 0 0-1-1H3z"/></svg>
                                                </span>
                                            @elseif($category === 'installments')
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-sky-50 text-sky-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/></svg>
                                                </span>
                                            @elseif($category === 'einvoice')
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-50 text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .064.016l6.857 3.94c.059.034.077.074.077.104v7.88a.25.25 0 0 1-.25.25H1.25A.25.25 0 0 1 1 13.94v-7.88c0-.03.018-.07.077-.104z"/><path fill="#fff" d="M7 7h2v4H7z"/><path fill="#fff" d="M7 11h2v2H7z"/></svg>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1z"/></svg>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="text-xs font-medium text-gray-900 truncate">{{ $data['title'] ?? 'تنبيه' }}</p>
                                                <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-xs text-gray-700 mt-0.5 line-clamp-2">{{ $data['body'] ?? '' }}</p>
                                            @if(!empty($data['url']))
                                                <a href="{{ $data['url'] }}" class="inline-flex items-center text-[11px] text-indigo-600 hover:text-indigo-700 mt-0.5">
                                                    فتح الشاشة
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-4 py-6 text-center text-xs text-gray-500">
                                        لا توجد تنبيهات حالياً.
                                    </div>
                                @endforelse
                            </div>
                            <div class="px-4 py-2 border-t border-gray-100 flex items-center justify-between text-xs bg-gray-50">
                                <form method="POST" action="{{ route('notifications.read-all') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="border-0 bg-transparent p-0 text-gray-600 hover:text-indigo-600">تمييز الكل كمقروء</button>
                                </form>
                                <a href="{{ route('notifications.index') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">عرض كل التنبيهات</a>
                            </div>
                        </div>
                    </div>
                    {{-- User menu: Bootstrap dropdown (أكثر موثوقية من Alpine مع Livewire/Filament على السيرفر) --}}
                    <div class="dropdown">
                        <button type="button"
                                id="navbarUserDropdown"
                                class="flex items-center gap-2 cursor-pointer rounded-lg border-0 bg-transparent p-1 ps-2 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-0"
                                data-bs-toggle="dropdown"
                                data-bs-display="dynamic"
                                data-bs-auto-close="true"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-label="قائمة المستخدم">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-medium text-white">{{ strtoupper(mb_substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                            <span class="hidden max-w-[10rem] truncate text-sm font-medium text-gray-700 sm:inline">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end mt-2 min-w-[12rem] rounded-xl border border-gray-200 bg-white py-2 shadow-lg"
                            style="font-family: 'Cairo', sans-serif; direction: rtl; text-align: right;"
                            aria-labelledby="navbarUserDropdown">
                            <li>
                                <a href="{{ route('profile.edit') }}" class="dropdown-item rounded-lg px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">الملف الشخصي</a>
                            </li>
                            @if(auth()->user()->isAdminOrSuperAdmin())
                                <li>
                                    <a href="{{ route('settings.company.edit') }}" class="dropdown-item rounded-lg px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">إعدادات المنشأة</a>
                                </li>
                                @if(auth()->user()->hasModule('pos'))
                                    <li>
                                        <a href="{{ route('settings.store.edit') }}" class="dropdown-item rounded-lg px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">إعدادات المتجر الإلكتروني</a>
                                    </li>
                                @endif
                                <li>
                                    <a href="{{ route('settings.api-tokens.index') }}" class="dropdown-item rounded-lg px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">مفاتيح الربط (API)</a>
                                </li>
                            @endif
                            @if(auth()->user()->is_super_admin)
                                <li>
                                    <a href="{{ route('super-admin.tenants.index') }}" class="dropdown-item rounded-lg px-4 py-2 text-sm text-indigo-700 hover:bg-indigo-50 font-medium">التحكم المركزي</a>
                                </li>
                            @endif
                            <li><hr class="dropdown-divider my-1 border-gray-100"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item w-100 rounded-lg px-4 py-2 text-right text-sm text-gray-700 hover:bg-gray-50 border-0 bg-transparent">تسجيل الخروج</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @endauth
            </div>
        </header>

        @auth
        {{-- مودال الوصول السريع للوحدات (Bootstrap فقط) --}}
        <div class="modal fade" id="erpModuleLauncherModal" tabindex="-1" aria-labelledby="erpModuleLauncherModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content rounded-2xl border border-gray-200 shadow-2xl" dir="rtl" style="font-family: 'Cairo', sans-serif;">
                    <div class="modal-header border-bottom border-gray-100 py-3 px-4 sm:px-5">
                        <div class="min-w-0 text-right flex-grow-1">
                            <h2 class="modal-title text-lg font-bold text-indigo-950 sm:text-xl mb-0" id="erpModuleLauncherModalLabel">الوصول السريع للوحدات</h2>
                            <p class="mt-1 mb-0 text-sm text-gray-500">اختر وحدة للانتقال مباشرة إلى لوحتها</p>
                        </div>
                        <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body p-4 sm:p-6 pt-2">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6">
                            {{-- لا تستخدم data-bs-dismiss على <a>: يمنع Bootstrap التنقل في كثير من الحالات --}}
                            @foreach ($quickAccessModules as $mod)
                                <a href="{{ route($mod['route']) }}"
                                   class="erp-module-quick-card group flex flex-col items-center gap-3 rounded-xl border border-gray-100 bg-gray-50/80 p-4 text-center text-decoration-none shadow-sm transition duration-200 hover:scale-[1.02] hover:border-indigo-200 hover:bg-white hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-xl transition group-hover:scale-105" style="background: {{ $mod['iconBg'] }}; color: {{ $mod['iconColor'] }};">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">{!! $mod['icon'] !!}</svg>
                                    </span>
                                    <span class="text-base font-semibold text-gray-900">{{ $mod['label'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $mod['subtitle'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endauth
