@extends('layouts.app')

@section('title', 'قواعد العمولات - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.commissions.index') }}" class="text-gray-500 hover:text-indigo-600">العمولات</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">قواعد العمولات</span>
@endsection

@section('content')
<div class="max-w-full">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales.commissions.index') }}" class="text-gray-500 hover:text-indigo-600" title="العودة للعمولات">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">قواعد العمولات</h1>
        </div>
        <button type="button" id="btn-open-rule-modal" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white font-medium text-sm transition shadow-sm" style="background: #2563eb;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
            قاعدة جديدة
        </button>
    </div>

    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">القواعد النشطة</h2>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium">اسم القاعدة</th>
                        <th class="py-3 px-4 font-medium">النوع</th>
                        <th class="py-3 px-4 font-medium">الأساس</th>
                        <th class="py-3 px-4 font-medium">المعدل</th>
                        <th class="py-3 px-4 font-medium">الأولوية</th>
                        <th class="py-3 px-4 font-medium">ساري من</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $r)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/50">
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $r->name }}</td>
                            <td class="py-3 px-4 text-gray-700">نسبة مئوية</td>
                            <td class="py-3 px-4 text-gray-700">الإيرادات</td>
                            <td class="py-3 px-4 text-gray-900">{{ number_format($r->rate_percent, 2) }}%</td>
                            <td class="py-3 px-4 text-gray-700">{{ $r->priority }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $r->valid_from?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($r->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-gray-500">لا توجد قواعد عمولات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rules->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $rules->links() }}
            </div>
        @endif
    </div>

    {{-- نافذة قاعدة جديدة --}}
    <div id="rule-modal-backdrop" class="fixed inset-0 bg-black/40 z-40 hidden"></div>
    <div id="rule-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 w-full max-w-2xl max-h-[90vh] overflow-y-auto mx-auto" dir="rtl">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                <h2 class="text-lg font-semibold text-gray-900">قاعدة جديدة</h2>
                <button type="button" id="btn-close-rule-modal" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('sales.commissions.rules.store') }}" class="p-5 space-y-4" dir="rtl">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم القاعدة <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="مثال: عمولة المبيعات القياسية" value="{{ old('name') }}">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="وصف القاعدة">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">النوع <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="percentage" {{ old('type', 'percentage') === 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الأساس <span class="text-red-500">*</span></label>
                        <select name="basis" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                            <option value="revenue" {{ old('basis', 'revenue') === 'revenue' ? 'selected' : '' }}>الإيرادات</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">النسبة % <span class="text-red-500">*</span></label>
                    <input type="number" inputmode="decimal" name="rate_percent" step="any" min="0" max="100" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="{{ old('rate_percent', '0') }}">
                    @error('rate_percent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الحد الأدنى</label>
                        <input type="number" inputmode="decimal" name="min_amount" step="any" min="0" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="{{ old('min_amount') }}">
                        <p class="mt-1 text-xs text-gray-500">الحد الأدنى لمبلغ العمولة للمعاملة</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الحد الأقصى</label>
                        <input type="number" inputmode="decimal" name="max_amount" step="any" min="0" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="{{ old('max_amount') }}">
                        <p class="mt-1 text-xs text-gray-500">الحد الأقصى لمبلغ العمولة</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ساري من <span class="text-red-500">*</span></label>
                        <input type="date" name="valid_from" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="{{ old('valid_from', now()->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ساري حتى</label>
                        <input type="date" name="valid_until" class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="{{ old('valid_until') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الأولوية <span class="text-red-500">*</span></label>
                        <input type="number" inputmode="decimal" name="priority" min="0" step="any" required class="w-full px-3 py-2.5 pr-4 text-right bg-gray-50 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="{{ old('priority', '1') }}">
                        <p class="mt-1 text-xs text-gray-500">رقم أقل = أولوية أعلى</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                    <button type="button" id="btn-cancel-rule-modal" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">إلغاء</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var openBtn = document.getElementById('btn-open-rule-modal');
    var closeBtn = document.getElementById('btn-close-rule-modal');
    var cancelBtn = document.getElementById('btn-cancel-rule-modal');
    var modal = document.getElementById('rule-modal');
    var backdrop = document.getElementById('rule-modal-backdrop');

    function openModal() {
        if (modal) modal.classList.remove('hidden');
        if (backdrop) backdrop.classList.remove('hidden');
    }
    function closeModal() {
        if (modal) modal.classList.add('hidden');
        if (backdrop) backdrop.classList.add('hidden');
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
})();
</script>
@endpush
@endsection
