@extends('layouts.app')

@section('title', 'حركات المخزون - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('inventory.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المخزون</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">حركات المخزون</span>
@endsection

@push('styles')
<style>
    .mov-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; }
    .mov-panel thead th { background-color: #f9fafb !important; color: #374151 !important; font-weight: 600 !important; border-bottom: 1px solid #e5e7eb; }
    .mov-page-title { display: flex; align-items: center; gap: 0.5rem; }
    .mov-page-title .inv-icon { color: #374151; }
    .mov-tools { display: flex; align-items: center; gap: 0.75rem; }
    .mov-tools .btn { height: 2.5rem; border-radius: 0.5rem; border: 1px solid #e5e7eb; display: inline-flex; align-items: center; gap: 0.35rem; }
    .mov-tools .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .mov-tools label { display: inline-flex; align-items: center; gap: 0.35rem; margin: 0; font-size: 0.875rem; color: #4b5563; }
    .mov-filter-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem 1.25rem; }
    .mov-filter-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; align-items: end; }
    @media (max-width: 991px) { .mov-filter-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .mov-filter-grid { grid-template-columns: 1fr; } }
    .mov-filter-card .form-label { font-size: 0.8125rem; color: #374151; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 0.25rem; }
    .mov-filter-card .form-control,
    .mov-filter-card .form-select { height: 2.5rem; min-height: 2.5rem; border: 1px solid #e5e7eb; background: #fff; border-radius: 0.5rem; font-size: 0.875rem; }
    .mov-filter-card .form-control:focus,
    .mov-filter-card .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.2); }
    .mov-filter-card .btn-primary { height: 2.5rem; border-radius: 0.5rem; background: #2563eb; border-color: #2563eb; display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem; }
    .mov-qty-in { color: #059669; font-weight: 600; }
    .mov-qty-out { color: #dc2626; font-weight: 600; }
    .mov-type-icon { width: 1.25rem; height: 1.25rem; display: inline-block; vertical-align: middle; margin-left: 0.25rem; }
    .mov-refresh-wrap { display: inline-flex; align-items: center; gap: 0.5rem; }
    .mov-last-updated { font-size: 0.8125rem; color: #9ca3af; white-space: nowrap; }
    .mov-refresh-btn.loading .mov-refresh-icon { animation: mov-spin 0.8s linear infinite; }
    .mov-refresh-btn.loading { pointer-events: none; }
    @keyframes mov-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    {{-- شريط العنوان والأدوات --}}
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">حركات المخزون</h1>
                <p class="mt-1 text-sm text-gray-500">سجل الوارد والصادر والجرد حسب المستودع والمنتج.</p>
            </div>
        </div>
        <div class="mov-tools flex flex-wrap items-center gap-3">
            <div class="mov-refresh-wrap flex items-center gap-2">
                <button type="button" id="mov-smart-refresh" class="mov-refresh-btn inline-flex h-10 items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50" title="تحديث البيانات">
                    <span class="mov-refresh-icon inline-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
                    </span>
                    تحديث
                </button>
                <span id="mov-last-updated" class="mov-last-updated text-sm text-gray-400">تم التحديث الآن</span>
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" id="show-balance-after" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"> إظهار الرصيد بعد الحركة
            </label>
        </div>
    </header>

    {{-- بطاقة التصفية --}}
    <form method="GET" action="{{ route('inventory.movements.index') }}" id="movements-filter-form" class="mov-filter-card rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mov-filter-grid">
            <div>
                <label class="form-label"><x-info field="inventory.movement_filter_warehouse" /> المستودع</label>
                <select name="warehouse_id" class="form-select w-100">
                    <option value="">— الكل —</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" {{ request('warehouse_id') == $w->id ? 'selected' : '' }}>{{ $w->name_ar ?? $w->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label"><x-info field="inventory.movement_filter_product" /> المنتج</label>
                <select name="item_id" class="form-select w-100">
                    <option value="">— الكل —</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}" {{ request('item_id') == $it->id ? 'selected' : '' }}>{{ $it->name_ar ?? $it->code }} ({{ $it->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label"><x-info field="inventory.movement_filter_type" /> نوع الحركة</label>
                <select name="movement_type" class="form-select w-100">
                    <option value="">— الكل —</option>
                    @foreach($types as $key => $t)
                        <option value="{{ $key }}" {{ request('movement_type') === $key ? 'selected' : '' }}>{{ $t['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label"><x-info field="inventory.movement_filter_date" /> من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control w-100">
            </div>
            <div>
                <label class="form-label">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control w-100">
            </div>
            <div class="flex items-end">
                <button type="submit" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
                    التصفية
                </button>
            </div>
        </div>
    </form>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="mov-panel overflow-x-auto">
        <table class="w-full min-w-[900px] border-collapse text-sm" id="movements-table">
            <thead>
                <tr class="bg-gray-50 text-gray-700">
                    <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.movement_date" /> التاريخ</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.movement_type" /> النوع</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.movement_product" /> المنتج</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.movement_warehouse" /> المستودع</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.movement_quantity" /> الكمية</th>
                    <th class="border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="inventory.movement_reference" /> المرجع</th>
                    <th class="mov-balance-col border-b border-gray-200 px-3 py-3 text-right font-semibold" style="display: none;"><x-info field="inventory.movement_balance_after" /> الرصيد بعد الحركة</th>
                </tr>
            </thead>
            <tbody id="movements-tbody">
                @forelse($movements as $m)
                <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/60">
                    <td class="whitespace-nowrap px-3 py-3 text-gray-800">{{ $m->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="px-3 py-3 text-gray-800">
                        @php $typeInfo = $types[$m->movement_type] ?? ['label' => $m->movement_type, 'icon' => '']; @endphp
                        @if(($typeInfo['icon'] ?? '') === 'in')
                            <span class="mov-type-icon text-emerald-600" title="وارد"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5z"/></svg></span>
                        @elseif(($typeInfo['icon'] ?? '') === 'out')
                            <span class="mov-type-icon text-red-600" title="صادر"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4z"/></svg></span>
                        @else
                            <span class="mov-type-icon text-gray-500" title="جرد"><svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M2 1.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-1zm0 3a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-1z"/></svg></span>
                        @endif
                        {{ $typeInfo['label'] }}
                    </td>
                    <td class="px-3 py-3">
                        <span class="font-semibold text-gray-900">{{ $m->item?->code ?? '—' }}</span>
                        <span class="block text-xs text-gray-500">{{ $m->item?->name_ar ?? $m->item?->name_en ?? '—' }}</span>
                    </td>
                    <td class="px-3 py-3 text-gray-800">{{ $m->warehouse?->name_ar ?? $m->warehouse?->code ?? '—' }}</td>
                    <td class="px-3 py-3">
                        @php $q = (float) $m->quantity; @endphp
                        <span class="{{ $q >= 0 ? 'mov-qty-in' : 'mov-qty-out' }}">
                            {{ $q >= 0 ? '+' : '' }}{{ number_format($q, 2) }}
                        </span>
                    </td>
                    <td class="px-3 py-3">
                        @if($m->reference_url && $m->reference_number)
                            <a href="{{ $m->reference_url }}" class="font-medium text-blue-600 hover:underline">{{ $m->reference_number }}</a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="mov-balance-col px-3 py-3 font-semibold tabular-nums text-gray-900" style="display: none;">{{ number_format($m->balance_after ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-3 py-10 text-center text-gray-500">لا توجد حركات مخزون</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function() {
    var showBalance = document.getElementById('show-balance-after');
    var balanceCols = document.querySelectorAll('.mov-balance-col');
    if (showBalance) {
        showBalance.addEventListener('change', function() {
            var show = this.checked;
            balanceCols.forEach(function(el) { el.style.display = show ? '' : 'none'; });
        });
    }

    // Smart Refresh: مؤقت آخر تحديث + AJAX
    var refreshBtn = document.getElementById('mov-smart-refresh');
    var lastUpdatedEl = document.getElementById('mov-last-updated');
    var filterForm = document.getElementById('movements-filter-form');
    var tbody = document.getElementById('movements-tbody');
    var baseUrl = '{{ route("inventory.movements.index") }}';
    var secondsSinceRefresh = 0;
    var timerId = null;

    function buildRefreshUrl() {
        if (!filterForm) return baseUrl;
        var fd = new FormData(filterForm);
        var params = new URLSearchParams();
        fd.forEach(function(v, k) { if (v) params.set(k, v); });
        var qs = params.toString();
        return qs ? baseUrl + '?' + qs : baseUrl;
    }

    function setLastUpdatedText() {
        if (!lastUpdatedEl) return;
        if (secondsSinceRefresh === 0) {
            lastUpdatedEl.textContent = 'تم التحديث الآن';
            return;
        }
        if (secondsSinceRefresh === 1) {
            lastUpdatedEl.textContent = 'تم التحديث منذ لحظة';
            return;
        }
        if (secondsSinceRefresh < 60) {
            lastUpdatedEl.textContent = 'تم التحديث منذ ' + secondsSinceRefresh + ' ثوانٍ';
            return;
        }
        var m = Math.floor(secondsSinceRefresh / 60);
        if (m === 1) lastUpdatedEl.textContent = 'تم التحديث منذ دقيقة';
        else if (m === 2) lastUpdatedEl.textContent = 'تم التحديث منذ دقيقتين';
        else if (m < 11) lastUpdatedEl.textContent = 'تم التحديث منذ ' + m + ' دقائق';
        else lastUpdatedEl.textContent = 'تم التحديث منذ ' + m + ' دقيقة';
    }

    function startTimer() {
        if (timerId) clearInterval(timerId);
        secondsSinceRefresh = 0;
        setLastUpdatedText();
        timerId = setInterval(function() {
            secondsSinceRefresh++;
            setLastUpdatedText();
        }, 1000);
    }

    function stopLoading() {
        if (refreshBtn) refreshBtn.classList.remove('loading');
    }

    if (refreshBtn && tbody) {
        refreshBtn.addEventListener('click', function() {
            refreshBtn.classList.add('loading');
            var url = buildRefreshUrl();
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                tbody.innerHTML = html;
                var show = showBalance && showBalance.checked;
                document.querySelectorAll('.mov-balance-col').forEach(function(el) { el.style.display = show ? '' : 'none'; });
                stopLoading();
                startTimer();
            })
            .catch(function() {
                stopLoading();
                if (lastUpdatedEl) lastUpdatedEl.textContent = 'فشل التحديث';
            });
        });
    }
    startTimer();
})();
</script>
@endpush
@endsection
