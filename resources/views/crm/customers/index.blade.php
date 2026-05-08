@extends('layouts.crm')

@section('title', ($isLeadsView ?? false) ? 'العملاء المحتملين — CRM — '.config('app.name') : 'جهات الاتصال — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    @if($isLeadsView ?? false)
        <span class="text-indigo-900 font-semibold truncate">العملاء المحتملين</span>
    @else
        <span class="text-indigo-900 font-semibold truncate">جهات الاتصال</span>
    @endif
@endsection

@section('content')
@php
    $crmAssigneeFilterOptions = $assignees->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values()->all();
    $crmSourceFilterOptions = collect($sourceOptions ?? [])->map(fn ($s) => ['value' => (string) $s, 'label' => (string) $s])->values()->all();
    $crmLeadPriorityOptions = [
        ['value' => 'high', 'label' => 'عالية'],
        ['value' => 'medium', 'label' => 'متوسطة'],
        ['value' => 'low', 'label' => 'منخفضة'],
    ];
    $crmStatusFilterOptions = [
        ['value' => 'potential', 'label' => 'محتمل'],
        ['value' => 'interested', 'label' => 'مهتم'],
        ['value' => 'active', 'label' => 'نشط'],
        ['value' => 'not_interested', 'label' => 'غير مهتم'],
    ];
@endphp
<div class="space-y-6" dir="rtl">
    @if($isLeadsView ?? false)
        {{-- شاشة العملاء المحتملين (محتوى رئيسي فقط — بدون تعديل السايدبار) --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">العملاء المحتملين</h1>
                    <span class="inline-flex items-center gap-1.5 shrink-0">
                        <x-info field="crm.leads_title_intro" />
                        <x-info field="crm.leads_filter_section" />
                    </span>
                </div>
            </div>
            <a href="{{ route('crm.customers.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm bg-blue-600 hover:bg-blue-700 transition border-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                عميل محتمل جديد
            </a>
        </div>

        <form id="crm-leads-filter-form" method="GET" action="{{ route('crm.customers.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm px-4 pt-5 pb-5 sm:px-5">
            <input type="hidden" name="crm_status" value="potential">
            {{-- صف التصفية يبدأ مباشرة من محاذاة بطاقة الجدول (بدون صف عنوان مع أيقونة) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                <div class="min-w-0">
                    <label for="leads_assigned_user_id" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">مسؤول عنه <x-info field="crm.assignee" /></span></label>
                    <x-searchable-select
                        name="assigned_user_id"
                        id="leads_assigned_user_id"
                        :options="$crmAssigneeFilterOptions"
                        :value="request('assigned_user_id', '')"
                        empty-label="الكل"
                        placeholder="ابحث بالاسم…"
                    />
                </div>
                <div class="min-w-0">
                    <label for="leads_source" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">المصدر <x-info field="crm.crm_source" /></span></label>
                    <x-searchable-select
                        name="source"
                        id="leads_source"
                        :options="$crmSourceFilterOptions"
                        :value="request('source', '')"
                        empty-label="الكل"
                        placeholder="ابحث بالمصدر…"
                    />
                </div>
                <div class="min-w-0">
                    <label for="leads_lead_priority" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الأولوية <x-info field="crm.lead_priority_field" /></span></label>
                    <x-searchable-select
                        name="lead_priority"
                        id="leads_lead_priority"
                        :options="$crmLeadPriorityOptions"
                        :value="request('lead_priority', '')"
                        empty-label="الكل"
                        placeholder="ابحث…"
                        :searchable="false"
                    />
                </div>
                <div class="min-w-0">
                    <label for="leads-search-q" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        </span>
                        <input type="search" name="q" id="leads-search-q" value="{{ request('q') }}" autocomplete="off" placeholder="بحث" class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden relative">
            <div id="crm-leads-loading-overlay" class="hidden absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-[1px]" aria-live="polite" aria-busy="false">
                <p class="text-sm font-medium text-gray-500">جاري التحميل...</p>
            </div>
            <div class="overflow-x-auto">
                <table class="table-fixed w-full min-w-[52rem] border-collapse text-sm text-right">
                    <colgroup>
                        <col class="w-[10%]">
                        <col class="w-[26%]">
                        <col class="w-[16%]">
                        <col class="w-[14%]">
                        <col class="w-[10%]">
                        <col class="w-[10%]">
                        <col class="w-[14%]">
                    </colgroup>
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.lead_number_column" /> رقم العميل</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="sales.customers_table_name" /> الاسم</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.leads_phone_column" /> الهاتف</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.crm_status" /> الحالة التسويقية</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.lead_priority_field" /> الأولوية</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap"><span class="inline-flex items-center gap-1"><x-info field="crm.leads_rating_column" /> التقييم</span></th>
                            <th scope="col" class="sticky left-0 z-10 py-3 px-3 font-medium text-center bg-gray-50 border-gray-200 shadow-[inset_1px_0_0_0_rgb(229_231_235)] whitespace-nowrap"><span class="inline-flex items-center justify-center gap-1"><x-info field="crm.crm_list_actions" /> إجراءات</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($customers as $customer)
                            @php
                                $stage = $customer->crm_status ?? 'potential';
                                [$crmLabel, $crmClass] = match ($stage) {
                                    'interested' => ['مهتم', 'bg-sky-50 text-sky-800 border border-sky-100'],
                                    'active' => ['نشط', 'bg-emerald-50 text-emerald-800 border border-emerald-100'],
                                    'not_interested' => ['غير مهتم', 'bg-red-50 text-red-800 border border-red-100'],
                                    default => ['محتمل', 'bg-violet-50 text-violet-900 border border-violet-100'],
                                };
                                [$prioLabel, $prioClass] = match ($customer->lead_priority) {
                                    'high' => ['عالية', 'bg-rose-50 text-rose-800 border border-rose-100'],
                                    'medium' => ['متوسطة', 'bg-amber-50 text-amber-900 border border-amber-100'],
                                    'low' => ['منخفضة', 'bg-teal-50 text-teal-800 border border-teal-100'],
                                    default => ['—', 'bg-gray-50 text-gray-500 border border-gray-100'],
                                };
                                $rating = (int) ($customer->lead_rating ?? 0);
                                $phoneDisp = $customer->phone ?: $customer->mobile;
                                $waDigits = preg_replace('/\D+/', '', (string) ($phoneDisp ?? ''));
                                $assignee = $customer->assignedUser;
                                $assigneeInitials = '';
                                if ($assignee) {
                                    $parts = preg_split('/\s+/u', trim($assignee->name), -1, PREG_SPLIT_NO_EMPTY);
                                    foreach (array_slice($parts, 0, 2) as $p) {
                                        $assigneeInitials .= mb_substr($p, 0, 1);
                                    }
                                    $assigneeInitials = mb_strtoupper($assigneeInitials);
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="py-3 px-3 align-middle whitespace-nowrap tabular-nums text-gray-900 font-medium">{{ $customer->lead_number ?: '—' }}</td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap">
                                    <div class="flex items-center gap-2 min-w-0">
                                        @if($assignee)
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold leading-none text-indigo-800 ring-1 ring-indigo-200/80" title="{{ $assignee->name }}">{{ $assigneeInitials }}</span>
                                        @endif
                                        <a href="{{ route('crm.customers.show', $customer) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline truncate max-w-[14rem] sm:max-w-[18rem] inline-block align-middle" title="{{ $customer->display_name }}">{{ $customer->display_name }}</a>
                                    </div>
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap">
                                    @if($phoneDisp)
                                        <div class="flex items-center gap-2 justify-start">
                                            <span class="tabular-nums text-gray-800">{{ $phoneDisp }}</span>
                                            @if($waDigits !== '')
                                                <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100 hover:bg-emerald-100 transition" title="فتح واتساب" aria-label="واتساب">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-medium {{ $crmClass }}">{{ $crmLabel }}</span>
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-sm font-medium {{ $prioClass }}">{{ $prioLabel }}</span>
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap">
                                    <div class="flex items-center gap-0.5 justify-start text-amber-400" role="img" aria-label="التقييم {{ $rating }} من 5">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $rating)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" class="shrink-0" aria-hidden="true"><path fill="currentColor" d="M8 .5l2.2 4.46 4.93.72-3.57 3.48.84 4.91L8 11.77l-4.4 2.3.84-4.9L.87 5.68l4.93-.72z"/></svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" class="shrink-0 text-gray-200" aria-hidden="true"><path fill="currentColor" d="M8 .5l2.2 4.46 4.93.72-3.57 3.48.84 4.91L8 11.77l-4.4 2.3.84-4.9L.87 5.68l4.93-.72z"/></svg>
                                            @endif
                                        @endfor
                                    </div>
                                </td>
                                <td class="sticky left-0 z-[5] py-3 px-3 text-center align-middle whitespace-nowrap bg-white shadow-[inset_1px_0_0_0_rgb(229_231_235)] group-hover:bg-gray-50/80">
                                    <div class="relative inline-flex items-center justify-center">
                                        <button type="button"
                                                class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                                data-actions-menu="crm-cust-actions-{{ $customer->id }}"
                                                aria-haspopup="menu"
                                                aria-expanded="false"
                                                title="المزيد من الإجراءات"
                                                aria-label="المزيد من الإجراءات">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                            </svg>
                                        </button>
                                        <div id="crm-cust-actions-{{ $customer->id }}"
                                             class="erp-actions-menu hidden min-w-[13.5rem] max-w-[min(18rem,calc(100vw-1.5rem))] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                             style="list-style: none;"
                                             role="menu"
                                             dir="rtl">
                                            <button type="button"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 border-0 bg-transparent"
                                                    role="menuitem"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#crmQuickAppointmentModal"
                                                    data-appt-url="{{ route('crm.customers.actions.appointment', $customer) }}"
                                                    data-customer-label="{{ $customer->display_name }}">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">إضافة موعد</span>
                                            </button>
                                            <button type="button"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 border-0 bg-transparent"
                                                    role="menuitem"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#crmQuickCallModal"
                                                    data-call-url="{{ route('crm.customers.actions.call', $customer) }}"
                                                    data-customer-label="{{ $customer->display_name }}">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122L9.65 11.5a.678.678 0 0 1-.58-.122L6.864 9.65a.678.678 0 0 1-.122-.58l.122-.282a.678.678 0 0 0-.122-.58L4.286 4.714a.678.678 0 0 0-.58-.122L3.5 3.5a.678.678 0 0 1-.846-.172z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">تسجيل مكالمة</span>
                                            </button>
                                            <a href="{{ route('crm.customers.show', $customer) }}"
                                               class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 no-underline">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533z"/><circle cx="8" cy="4.5" r="1"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">عرض ملف العميل</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-sm text-gray-500 whitespace-nowrap">لا توجد نتائج مطابقة للفلاتر.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $customers->links() }}</div>
            @endif
        </div>
    @else
        @php
            $contactsStats = is_array($contactsStats ?? null) ? $contactsStats : ['total' => 0, 'leads' => 0, 'customers_active' => 0];
            $sortCols = is_array($sortForView ?? null) ? $sortForView : ['sortColumn' => 'created_at', 'sortDirection' => 'desc'];
            $contactsSortColumn = $sortCols['sortColumn'] ?? 'created_at';
            $contactsSortDirection = $sortCols['sortDirection'] ?? 'desc';
            $contactsFilterBaseParams = collect(request()->except(['page']))
                ->reject(fn ($v) => $v === null || $v === '')
                ->all();
            $contactsSortNameDirection = (($contactsSortColumn === 'name' && $contactsSortDirection === 'asc') ? 'desc' : 'asc');
            $contactsSortCreatedDirection = (($contactsSortColumn === 'created_at' && $contactsSortDirection === 'desc') ? 'asc' : 'desc');
        @endphp
        {{-- جهات الاتصال — CRM --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900">جهات الاتصال</h1>
                    <span class="inline-flex items-center shrink-0"><x-info field="crm.contacts_page_intro" /></span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('crm.customers.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-900 text-sm font-semibold shadow-sm hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    عميل محتمل جديد
                </a>
                <a href="{{ route('crm.customers.new') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm bg-blue-600 hover:bg-blue-700 transition no-underline border-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    عميل جديد
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 flex items-center gap-4 min-h-[5.5rem]">
                <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>
                </span>
                <div class="min-w-0 flex-1 text-right space-y-1">
                    <p class="text-sm font-medium text-gray-700 leading-snug m-0 flex flex-wrap items-center justify-end gap-1.5">
                        <span>إجمالي جهات الاتصال</span>
                        <x-info field="crm.contacts_total_card" />
                    </p>
                    <p class="w-full text-left text-2xl font-bold text-gray-900 tabular-nums m-0 leading-tight">{{ number_format($contactsStats['total']) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 flex items-center gap-4 min-h-[5.5rem]">
                <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm3-8a.5.5 0 0 0-.5-.5h-2V4.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v2h-2a.5.5 0 0 0-.5.5v1c0 .28.224.5.5.5h2v2c0 .28.224.5.5.5h1c.278 0 .5-.223.5-.5v-2h2c.278 0 .5-.223.5-.5v-1z"/></svg>
                </span>
                <div class="min-w-0 flex-1 text-right space-y-1">
                    <p class="text-sm font-medium text-gray-700 leading-snug m-0 flex flex-wrap items-center justify-end gap-1.5">
                        <span>العملاء المحتملين</span>
                        <x-info field="crm.contacts_leads_card" />
                    </p>
                    <p class="w-full text-left text-2xl font-bold text-gray-900 tabular-nums m-0 leading-tight">{{ number_format($contactsStats['leads']) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 flex items-center gap-4 min-h-[5.5rem] sm:col-span-2 xl:col-span-1">
                <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M2.868.669A1 1 0 0 0 1 1.449v13.086a1 1 0 0 0 1.555.832l11-6.849a1 1 0 0 0 0-1.697l-11-6.85a1 1 0 0 0-.687-.113z"/></svg>
                </span>
                <div class="min-w-0 flex-1 text-right space-y-1">
                    <p class="text-sm font-medium text-gray-700 leading-snug m-0 flex flex-wrap items-center justify-end gap-1.5">
                        <span>العملاء</span>
                        <x-info field="crm.contacts_active_customers_card" />
                    </p>
                    <p class="w-full text-left text-2xl font-bold text-gray-900 tabular-nums m-0 leading-tight">{{ number_format($contactsStats['customers_active']) }}</p>
                </div>
            </div>
        </div>

        <form id="crm-contacts-filter-form" method="GET" action="{{ route('crm.customers.index') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-visible">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-100 bg-gray-50/80 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <h2 class="text-base font-semibold text-gray-900 m-0 flex flex-wrap items-center gap-2">
                    <span>التصفية</span>
                    <x-info field="crm.contacts_filter_heading" />
                </h2>
                <span class="inline-flex shrink-0 text-sm text-gray-600 leading-snug"><x-info field="crm.contacts_filter_section" /></span>
            </div>
            <div class="px-4 py-5 sm:px-6 space-y-6">
                <div class="grid grid-cols-1 gap-4 lg:gap-6 md:grid-cols-4 md:items-end">
                    <div class="md:col-span-1 min-w-0">
                        <label for="contacts-search-q" class="block text-sm font-medium text-gray-700 mb-1.5"><span class="inline-flex items-center gap-1">البحث <x-info field="crm.contacts_search_label" /></span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-gray-400 z-[1]" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                            </span>
                            <input type="search" name="q" id="contacts-search-q" value="{{ request('q') }}" autocomplete="off" placeholder="البحث في جهات الاتصال…" dir="rtl" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 ps-3 pe-10 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="md:col-span-1 min-w-0">
                        <label for="contacts_filter_crm_status" class="block text-sm font-medium text-gray-700 mb-1.5"><span class="inline-flex items-center gap-1">الحالة <x-info field="crm.crm_status" /></span></label>
                        <div class="min-h-[2.75rem] flex items-stretch [&_.erp-searchable-select]:w-full">
                            <x-searchable-select
                                name="crm_status"
                                id="contacts_filter_crm_status"
                                :options="$crmStatusFilterOptions"
                                :value="request('crm_status', '')"
                                empty-label="الكل"
                                placeholder="كل الحالات…"
                                :searchable="false"
                            />
                        </div>
                    </div>
                    <div class="md:col-span-1 min-w-0 flex flex-col justify-end">
                        <div class="h-[1.3125rem] mb-1.5 max-md:hidden shrink-0" aria-hidden="true"></div>
                        <button type="submit" class="inline-flex w-full justify-center items-center min-h-[2.75rem] px-4 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">تطبيق</button>
                    </div>
                    <div class="md:col-span-1 min-w-0 flex flex-col justify-end">
                        <div class="h-[1.3125rem] mb-1.5 max-md:hidden shrink-0" aria-hidden="true"></div>
                        <a href="{{ route('crm.customers.index') }}" class="inline-flex w-full justify-center items-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition no-underline">مسح</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden relative">
            <div id="crm-contacts-loading-overlay" class="hidden absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-[1px]" aria-live="polite" aria-busy="false">
                <p class="text-sm font-medium text-gray-500">جاري التحميل...</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] border-collapse text-sm text-right table-auto">
                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                        <tr>
                            <th scope="col" class="py-3 px-3 font-medium align-top min-w-[10rem]">
                                @php $nameSortHref = route('crm.customers.index', array_merge($contactsFilterBaseParams, ['sort' => 'name', 'direction' => $contactsSortNameDirection])); @endphp
                                <a href="{{ $nameSortHref }}" class="inline-flex items-center gap-1 text-gray-700 hover:text-blue-700 no-underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="opacity-60 shrink-0" aria-hidden="true"><path d="M3.5 3.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.5-.5Zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.5-.5Zm0 3a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.5-.5Zm0 3a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.5-.5Z"/></svg>
                                    <span class="inline-flex items-center gap-1"><x-info field="crm.contacts_name_column" /> الاسم</span>
                                </a>
                            </th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap align-top w-[1%]"><span class="inline-flex items-center gap-1"><x-info field="crm.contacts_type_column" /> النوع</span></th>
                            <th scope="col" class="py-3 px-3 font-medium align-top min-w-[9rem]"><span class="inline-flex items-center gap-1"><x-info field="crm.contacts_email_column" /> البريد الإلكتروني</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap align-top w-[1%]"><span class="inline-flex items-center gap-1"><x-info field="crm.contacts_status_column" /> الحالة</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap align-top w-[1%]"><span class="inline-flex items-center gap-1"><x-info field="crm.contacts_phone_column" /> الهاتف</span></th>
                            <th scope="col" class="py-3 px-3 font-medium whitespace-nowrap align-top w-[1%]">
                                @php $createdSortHref = route('crm.customers.index', array_merge($contactsFilterBaseParams, ['sort' => 'created_at', 'direction' => $contactsSortCreatedDirection])); @endphp
                                <a href="{{ $createdSortHref }}" class="inline-flex items-center gap-1 text-gray-700 hover:text-blue-700 no-underline">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="opacity-60 shrink-0" aria-hidden="true"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h.5A1.5 1.5 0 0 1 15 2.5V14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V2.5A1.5 1.5 0 0 1 2.5 1H3V.5a.5.5 0 0 1 .5-.5ZM2 5v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V5H2Z"/></svg>
                                    <span class="inline-flex items-center gap-1"><x-info field="crm.contacts_created_column" /> تاريخ الإنشاء</span>
                                </a>
                            </th>
                            <th scope="col" class="sticky left-0 z-10 py-3 px-3 font-medium text-center bg-gray-50 border-gray-200 shadow-[inset_1px_0_0_0_rgb(229_231_235)] whitespace-nowrap align-top w-[7.5rem] min-w-[7.5rem]">
                                <span class="inline-flex items-center justify-center gap-1"><x-info field="crm.crm_list_actions" /> الإجراءات</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($customers as $customer)
                            @php
                                $stage = $customer->crm_status ?? 'potential';
                                [$crmLabel, $crmClass] = match ($stage) {
                                    'interested' => [\App\Models\Customer::labelForCrmStatus('interested'), 'bg-sky-50 text-sky-800 border border-sky-100'],
                                    'active' => [\App\Models\Customer::labelForCrmStatus('active'), 'bg-emerald-50 text-emerald-800 border border-emerald-100'],
                                    'not_interested' => [\App\Models\Customer::labelForCrmStatus('not_interested'), 'bg-red-50 text-red-800 border border-red-100'],
                                    default => [\App\Models\Customer::labelForCrmStatus('potential'), 'bg-violet-50 text-violet-900 border border-violet-100'],
                                };
                                $phoneDisp = $customer->phone ?: $customer->mobile;
                                $createdStr = optional($customer->created_at)?->timezone(config('app.timezone'))->format('Y/m/d H:i') ?? '—';
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="py-3 px-3 align-middle min-w-[10rem] max-w-[14rem]">
                                    <a href="{{ route('crm.customers.show', $customer) }}" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline block whitespace-normal break-words leading-snug">{{ $customer->display_name }}</a>
                                    @if($customer->code)<span class="block text-xs text-gray-500 tabular-nums mt-0.5">{{ $customer->code }}</span>@endif
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap text-gray-800">{{ \App\Models\Customer::contactRecordTypeLabel($customer->company_name) }}</td>
                                <td class="py-3 px-3 align-middle min-w-0">
                                    @if($customer->email)<span class="block text-gray-800 whitespace-normal break-all text-sm" dir="ltr">{{ $customer->email }}</span>@else<span class="text-gray-400">—</span>@endif
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium {{ $crmClass }}">{{ $crmLabel }}</span>
                                </td>
                                <td class="py-3 px-3 align-middle whitespace-nowrap tabular-nums text-gray-800">{{ $phoneDisp ?: '—' }}</td>
                                <td class="py-3 px-3 align-middle tabular-nums text-gray-600 whitespace-nowrap text-xs">{{ $createdStr }}</td>
                                <td class="sticky left-0 z-[5] py-2 px-2 text-center align-middle bg-white shadow-[inset_1px_0_0_0_rgb(229_231_235)] group-hover:bg-gray-50/80 w-[7.5rem] min-w-[7.5rem]">
                                    <div class="relative inline-flex items-center justify-center">
                                        <button type="button"
                                                class="erp-actions-trigger inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-50 transition shrink-0"
                                                data-actions-menu="crm-cust-contact-actions-{{ $customer->id }}"
                                                aria-haspopup="menu"
                                                aria-expanded="false"
                                                title="المزيد من الإجراءات"
                                                aria-label="المزيد من الإجراءات">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                            </svg>
                                        </button>
                                        <div id="crm-cust-contact-actions-{{ $customer->id }}"
                                             class="erp-actions-menu hidden min-w-[13.5rem] max-w-[min(18rem,calc(100vw-1.5rem))] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                             style="list-style: none;"
                                             role="menu"
                                             dir="rtl">
                                            <button type="button"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 border-0 bg-transparent"
                                                    role="menuitem"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#crmQuickAppointmentModal"
                                                    data-appt-url="{{ route('crm.customers.actions.appointment', $customer) }}"
                                                    data-customer-label="{{ $customer->display_name }}">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">إضافة موعد</span>
                                            </button>
                                            <button type="button"
                                                    class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 border-0 bg-transparent"
                                                    role="menuitem"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#crmQuickCallModal"
                                                    data-call-url="{{ route('crm.customers.actions.call', $customer) }}"
                                                    data-customer-label="{{ $customer->display_name }}">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122L9.65 11.5a.678.678 0 0 1-.58-.122L6.864 9.65a.678.678 0 0 1-.122-.58l.122-.282a.678.678 0 0 0-.122-.58L4.286 4.714a.678.678 0 0 0-.58-.122L3.5 3.5a.678.678 0 0 1-.846-.172z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">تسجيل مكالمة</span>
                                            </button>
                                            <a href="{{ route('crm.customers.show', $customer) }}"
                                               class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-gray-50 no-underline">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533z"/><circle cx="8" cy="4.5" r="1"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">عرض ملف العميل</span>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 text-center text-sm text-gray-500">لا توجد بيانات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $customers->links() }}</div>
            @endif
        </div>
    @endif
</div>

@include('crm.partials.activity-modals')

@endsection

@push('scripts')
@if($isLeadsView ?? false)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('crm-leads-filter-form');
    var overlay = document.getElementById('crm-leads-loading-overlay');
    if (form && overlay) {
        form.addEventListener('submit', function () {
            overlay.classList.remove('hidden');
            overlay.setAttribute('aria-busy', 'true');
        });
    }
    if (form) {
        form.addEventListener('searchable-select-change', function (e) {
            var d = e.detail;
            if (!d || !['assigned_user_id', 'source', 'lead_priority'].includes(d.name)) {
                return;
            }
            form.requestSubmit();
        });
    }
    var searchInput = document.getElementById('leads-search-q');
    var searchTimer;
    if (searchInput && searchInput.form) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchInput.form.requestSubmit();
            }, 450);
        });
    }
});
</script>
@endif
@unless($isLeadsView ?? false)
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('crm-contacts-filter-form');
    var overlay = document.getElementById('crm-contacts-loading-overlay');
    if (form && overlay) {
        form.addEventListener('submit', function () {
            overlay.classList.remove('hidden');
            overlay.setAttribute('aria-busy', 'true');
        });
        form.addEventListener('searchable-select-change', function (e) {
            var d = e.detail;
            if (!d || !['assigned_user_id', 'source', 'crm_status'].includes(d.name)) {
                return;
            }
            form.requestSubmit();
        });
    }
    var searchInput = document.getElementById('contacts-search-q');
    var searchTimer;
    if (searchInput && searchInput.form) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                searchInput.form.requestSubmit();
            }, 450);
        });
    }
});
</script>
@endunless
@endpush
