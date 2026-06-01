@extends('layouts.app')

@section('title', $productionOrder->production_number . ' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('production-orders.index') }}" class="text-gray-500 hover:text-indigo-600">أوامر الإنتاج</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $productionOrder->production_number }}</span>
@endsection

@section('content')
@php
    $typeLabels = ['raw_material' => 'مادة خام', 'finished_good' => 'منتج تام', 'service' => 'خدمة'];
    $statusLabels = [
        'pending' => 'معلق',
        'in_progress' => 'قيد التنفيذ',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ];
    $canComplete = in_array($productionOrder->status, ['pending', 'in_progress'], true);
@endphp
<div class="max-w-full" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">{{ session('info') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex flex-wrap items-center gap-2">
                {{ $productionOrder->production_number }}
                <x-info field="production.order_number" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">أمر إنتاج — تحديث المخزون عند الإتمام</p>
        </div>
        <div class="flex flex-wrap items-center gap-2 justify-end">
            <a href="{{ route('production-orders.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">القائمة</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="text-gray-500 inline-flex items-center gap-1">الحالة <x-info field="production.order_status" /></span>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($productionOrder->status === 'pending') bg-amber-100 text-amber-800
                    @elseif($productionOrder->status === 'in_progress') bg-blue-100 text-blue-800
                    @elseif($productionOrder->status === 'completed') bg-green-100 text-green-800
                    @else bg-gray-100 text-gray-700 @endif">
                    {{ $statusLabels[$productionOrder->status] ?? $productionOrder->status }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 inline-flex items-center gap-1">تاريخ البداية <x-info field="production.start_date" /></span>
                <span class="font-medium text-gray-900">{{ $productionOrder->start_date?->format('Y-m-d') ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 inline-flex items-center gap-1">تاريخ النهاية <x-info field="production.end_date" /></span>
                <span class="font-medium text-gray-900">{{ $productionOrder->end_date?->format('Y-m-d') ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 inline-flex items-center gap-1">مستودع الخامات <x-info field="production.raw_materials_warehouse" /></span>
                <span class="font-medium text-gray-900">{{ $productionOrder->rawMaterialsWarehouse ? $productionOrder->rawMaterialsWarehouse->code.' — '.$productionOrder->rawMaterialsWarehouse->name_ar : '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 inline-flex items-center gap-1">مستودع المنتج التام <x-info field="production.finished_goods_warehouse" /></span>
                <span class="font-medium text-gray-900">{{ $productionOrder->finishedGoodsWarehouse ? $productionOrder->finishedGoodsWarehouse->code.' — '.$productionOrder->finishedGoodsWarehouse->name_ar : '—' }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-gray-800 inline-flex items-center gap-1">المنتج التام <x-info field="production.finished_item" /></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الصنف <x-info field="production.col_item" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">النوع <x-info field="production.col_type" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">مخطط <x-info field="production.col_planned" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">منتَج <x-info field="production.col_produced" /></span></th>
                        @if($canComplete)
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الرصيد الحالي <x-info field="production.col_stock" /></span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionOrder->productionItems as $line)
                        @php $t = $line->item?->type; @endphp
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">{{ $line->item?->code }} — {{ $line->item?->name_ar }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $typeLabels[$t] ?? $t }}</td>
                            <td class="py-3 px-4">{{ rtrim(rtrim(number_format((float) $line->planned_quantity, 4, '.', ''), '0'), '.') }}</td>
                            <td class="py-3 px-4 font-medium">{{ rtrim(rtrim(number_format((float) $line->produced_quantity, 4, '.', ''), '0'), '.') }}</td>
                            @if($canComplete)
                                <td class="py-3 px-4 text-gray-600">{{ rtrim(rtrim(number_format((float) ($line->item?->current_stock ?? 0), 4, '.', ''), '0'), '.') }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-gray-800 inline-flex items-center gap-1">المواد الخام <x-info field="production.ingredients_section" /></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الصنف <x-info field="production.col_item" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">النوع <x-info field="production.col_type" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">كمية الاستهلاك <x-info field="production.col_consume" /></span></th>
                        @if($canComplete)
                            <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الرصيد الحالي <x-info field="production.col_stock" /></span></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionOrder->ingredients as $row)
                        @php $t = $row->item?->type; @endphp
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4">{{ $row->item?->code }} — {{ $row->item?->name_ar }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $typeLabels[$t] ?? $t }}</td>
                            <td class="py-3 px-4 font-medium">{{ rtrim(rtrim(number_format((float) $row->quantity_to_consume, 4, '.', ''), '0'), '.') }}</td>
                            @if($canComplete)
                                <td class="py-3 px-4 text-gray-600">{{ rtrim(rtrim(number_format((float) ($row->item?->current_stock ?? 0), 4, '.', ''), '0'), '.') }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($canComplete && $productionOrder->ingredients->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6" dir="rtl"
             x-data="{
                loading: false,
                checked: false,
                hasShortage: false,
                shortages: [],
                errorMsg: '',
                async checkShortage() {
                    this.loading = true;
                    this.checked = false;
                    this.errorMsg = '';
                    try {
                        const r = await fetch('{{ route("production-orders.ingredient-shortage", $productionOrder) }}', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const d = await r.json();
                        if (!r.ok) {
                            this.shortages = [];
                            this.hasShortage = false;
                            this.errorMsg = d.message || 'تعذر التحقق من التوفر.';
                            this.checked = true;
                            return;
                        }
                        this.shortages = d.shortages || [];
                        this.hasShortage = !!d.has_shortage;
                    } catch (e) {
                        this.shortages = [];
                        this.hasShortage = false;
                        this.errorMsg = 'تعذر الاتصال بالخادم.';
                    } finally {
                        this.loading = false;
                        this.checked = true;
                    }
                }
            }">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900 inline-flex items-center gap-2">
                    التحقق من توفر الخامات
                    <x-info field="production.check_material_shortage" />
                </h2>
                <button type="button" @click="checkShortage()" :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-800 text-sm font-medium hover:bg-indigo-100 disabled:opacity-50">
                    <span x-show="loading" x-cloak>جاري التحقق...</span>
                    <span x-show="!loading">تحقق من توفر الخامات</span>
                </button>
            </div>
            <p x-show="errorMsg" x-text="errorMsg" x-cloak class="text-sm text-red-600 mb-3"></p>
            <div x-show="hasShortage && checked" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 p-4 space-y-3">
                <p class="text-sm text-amber-900 font-medium">تنبيه: توجد مواد خام غير كافية مقارنةً بالكمية المطلوبة للاستهلاك.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead>
                            <tr class="text-amber-900 font-medium border-b border-amber-200">
                                <th class="py-2 px-2">الصنف</th>
                                <th class="py-2 px-2">المطلوب</th>
                                <th class="py-2 px-2">المتاح</th>
                                <th class="py-2 px-2">الناقص</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="s in shortages" :key="s.item_id">
                                <tr class="border-b border-amber-100/60">
                                    <td class="py-2 px-2" x-text="s.code + ' — ' + s.name_ar"></td>
                                    <td class="py-2 px-2" x-text="s.needed"></td>
                                    <td class="py-2 px-2" x-text="s.available"></td>
                                    <td class="py-2 px-2 font-semibold" x-text="s.shortage"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <form method="POST" action="{{ route('production-orders.prefill-purchase', $productionOrder) }}" class="inline-flex">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #16a34a;">
                            إنشاء طلب شراء بالناقص
                        </button>
                    </form>
                    <x-info field="production.purchase_shortage_create" />
                </div>
            </div>
            <div x-show="checked && !hasShortage && !loading && !errorMsg" x-cloak class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                جميع الخامات المطلوبة متوفرة وفق الرصيد الحالي.
            </div>
        </div>
    @endif

    @if($canComplete)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 inline-flex items-center gap-2">
                إتمام الإنتاج (Complete Production)
                <x-info field="production.complete_action" />
            </h2>
            <form method="POST" action="{{ route('production-orders.complete', $productionOrder->id) }}" class="space-y-4" onsubmit="return confirm('تأكيد إتمام الإنتاج؟ سيتم خصم المواد الخام وإضافة المنتج التام إلى المخزون.');">
                @csrf
                @foreach($productionOrder->productionItems as $line)
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="inline-flex items-center gap-1">كمية الإنتاج الفعلية — {{ $line->item?->code }} <x-info field="production.produced_quantity" /></span>
                            </label>
                            <input type="number" inputmode="decimal" name="produced[{{ $line->id }}]" value="{{ old('produced.'.$line->id, rtrim(rtrim(number_format((float) $line->planned_quantity, 4, '.', ''), '0'), '.')) }}" min="0.0001" step="any" class="w-full max-w-xs py-2 px-3 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500" required>
                            @error('produced.'.$line->id)<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
                <div class="flex justify-end pt-2">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #16a34a;">Complete Production — إتمام الإنتاج</button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
