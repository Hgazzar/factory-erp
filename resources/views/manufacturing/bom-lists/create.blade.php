@extends('layouts.app')

@section('title', 'قائمة مواد جديدة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-indigo-600">التصنيع</a>
    <span>›</span>
    <a href="{{ route('manufacturing.bom-lists.index') }}" class="text-gray-500 hover:text-indigo-600">قوائم المواد</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جديد</span>
@endsection

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6" dir="rtl">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                    قائمة مواد جديدة
                    <x-info field="manufacturing.bom_list_create_intro" />
                </h1>
                <p class="text-sm text-gray-500 mt-1">تعريف قائمة مواد للمنتج التام مع المكونات والتكاليف</p>
            </div>
            <a href="{{ route('manufacturing.bom-lists.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">رجوع</a>
        </div>

        <form method="POST" action="{{ route('manufacturing.bom-lists.store') }}" id="bom-list-form" class="space-y-6">
            @csrf
            @php
                $bomItemOpts = $finishedGoods->map(fn ($it) => ['value' => $it->id, 'label' => $it->code.' — '.$it->name_ar])->all();
                $bomStatusOpts = [
                    ['value' => \App\Models\BomList::STATUS_DRAFT, 'label' => 'مسودة'],
                    ['value' => \App\Models\BomList::STATUS_ACTIVE, 'label' => 'نشط'],
                    ['value' => \App\Models\BomList::STATUS_OBSOLETE, 'label' => 'قديم'],
                ];
            @endphp

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
                <h2 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 inline-flex items-center gap-2">
                    تفاصيل قائمة المواد
                    <x-info field="manufacturing.bom_list_section_header" />
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">اختيار المنتج <span class="text-red-500">*</span> <x-info field="manufacturing.bom_list_field_product" /></span>
                        </label>
                        <x-custom-select
                            name="item_id"
                            id="bom_item_id"
                            class="w-full"
                            :options="$bomItemOpts"
                            :selected="old('item_id')"
                            placeholder="ابحث في المنتجات التامة..."
                            empty-label="— اختر المنتج التام —"
                            required
                            :error="$errors->has('item_id')"
                        />
                        @error('item_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">الاسم <span class="text-red-500">*</span> <x-info field="manufacturing.bom_list_field_name" /></span>
                        </label>
                        <input type="text" name="name" id="bom_name" value="{{ old('name') }}" required maxlength="255" class="w-full py-2.5 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="مثال: قائمة مواد — منتج أ">
                        @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">الإصدار <span class="text-red-500">*</span> <x-info field="manufacturing.bom_list_field_version" /></span>
                        </label>
                        <input type="text" name="version" value="{{ old('version', '1.0') }}" required maxlength="40" class="w-full py-2.5 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="1.0">
                        @error('version')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">الحالة <span class="text-red-500">*</span> <x-info field="manufacturing.bom_list_field_status" /></span>
                        </label>
                        <x-custom-select
                            name="status"
                            class="w-full"
                            :options="$bomStatusOpts"
                            :selected="old('status', \App\Models\BomList::STATUS_DRAFT)"
                            placeholder="اختر الحالة..."
                            :empty-option="false"
                            required
                            :error="$errors->has('status')"
                        />
                        @error('status')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">تكلفة العمالة <x-info field="manufacturing.bom_list_field_labor" /></span>
                        </label>
                        <input type="number" name="labor_cost" value="{{ old('labor_cost', '0') }}" min="0" step="0.01" class="w-full py-2.5 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('labor_cost')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">التكاليف العامة <x-info field="manufacturing.bom_list_field_overhead" /></span>
                        </label>
                        <input type="number" name="overhead_cost" value="{{ old('overhead_cost', '0') }}" min="0" step="0.01" class="w-full py-2.5 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('overhead_cost')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">
                            <span class="inline-flex items-center gap-1">ملاحظات الرأس <x-info field="manufacturing.bom_list_field_header_notes" /></span>
                        </label>
                        <textarea name="header_notes" rows="2" class="w-full py-2.5 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('header_notes') }}</textarea>
                        @error('header_notes')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/80">
                    <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">
                        المكونات
                        <x-info field="manufacturing.bom_list_components_section" />
                    </h2>
                    <button type="button" id="bom-add-line" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-blue-200 bg-white text-blue-700 text-sm font-semibold hover:bg-blue-50">
                        <span class="text-lg leading-none font-bold">+</span>
                        إضافة مكون
                    </button>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-sm text-right min-w-[720px]" id="bom-lines-table">
                        <thead class="text-gray-600 border-b border-gray-200">
                            <tr>
                                <th class="py-2 px-2 font-semibold"><span class="inline-flex items-center gap-1">المنتج (الخامة) <span class="text-red-500">*</span> <x-info field="manufacturing.bom_line_col_component" /></span></th>
                                <th class="py-2 px-2 font-semibold w-28"><span class="inline-flex items-center gap-1">الكمية <span class="text-red-500">*</span> <x-info field="manufacturing.bom_line_col_qty" /></span></th>
                                <th class="py-2 px-2 font-semibold w-28"><span class="inline-flex items-center gap-1">الوحدة <x-info field="manufacturing.bom_line_col_unit" /></span></th>
                                <th class="py-2 px-2 font-semibold w-24"><span class="inline-flex items-center gap-1">نسبة الهدر % <x-info field="manufacturing.bom_line_col_scrap" /></span></th>
                                <th class="py-2 px-2 font-semibold min-w-[140px]"><span class="inline-flex items-center gap-1">ملاحظات <x-info field="manufacturing.bom_line_col_notes" /></span></th>
                                <th class="py-2 px-2 w-14"></th>
                            </tr>
                        </thead>
                        <tbody id="bom-lines-body">
                            @php
                                $oldLines = old('lines');
                                if (! is_array($oldLines) || $oldLines === []) {
                                    $oldLines = [['component_item_id' => '', 'quantity' => '', 'unit' => '', 'scrap_percent' => '0', 'notes' => '']];
                                }
                            @endphp
                            @foreach($oldLines as $idx => $row)
                                <tr class="bom-line-row border-b border-gray-100 align-top">
                                    <td class="py-2 px-2">
                                        <select name="lines[{{ $idx }}][component_item_id]" class="bom-comp-select w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500" required>
                                            <option value="">— اختر الخامة —</option>
                                            @foreach($rawMaterials as $rm)
                                                <option value="{{ $rm->id }}" data-unit="{{ e($rm->unit ?? '') }}" @selected(($row['component_item_id'] ?? '') == $rm->id)>{{ $rm->code }} — {{ $rm->name_ar }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" name="lines[{{ $idx }}][quantity]" value="{{ $row['quantity'] ?? '' }}" min="0.0001" step="any" required class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="text" name="lines[{{ $idx }}][unit]" value="{{ $row['unit'] ?? '' }}" class="bom-unit-input w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500" placeholder="—">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="number" name="lines[{{ $idx }}][scrap_percent]" value="{{ $row['scrap_percent'] ?? '0' }}" min="0" max="100" step="0.01" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500">
                                    </td>
                                    <td class="py-2 px-2">
                                        <input type="text" name="lines[{{ $idx }}][notes]" value="{{ $row['notes'] ?? '' }}" maxlength="500" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500">
                                    </td>
                                    <td class="py-2 px-2 text-center">
                                        <button type="button" class="bom-remove-line text-red-600 hover:text-red-800 text-sm font-semibold px-1">حذف</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @error('lines')<p class="text-red-600 text-xs mt-2">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700">حفظ قائمة المواد</button>
            </div>
        </form>
    </div>
</div>

<template id="bom-line-select-options">
    <option value="">— اختر الخامة —</option>
    @foreach($rawMaterials as $rm)
        <option value="{{ $rm->id }}" data-unit="{{ e($rm->unit ?? '') }}">{{ $rm->code }} — {{ $rm->name_ar }}</option>
    @endforeach
</template>

@push('scripts')
<script>
(function () {
    const nameInput = document.getElementById('bom_name');
    const FG_NAME_BY_ID = @json($finishedGoods->mapWithKeys(fn ($it) => [(string) $it->id => $it->name_ar])->all());
    document.addEventListener('custom-select-change', function (e) {
        if (!e.detail || e.detail.name !== 'item_id' || !nameInput || nameInput.value) return;
        const id = e.detail.value;
        if (!id) return;
        const n = FG_NAME_BY_ID[String(id)];
        if (n) nameInput.value = 'قائمة مواد — ' + n;
    });

    function fillUnitFromSelect(row) {
        const sel = row.querySelector('.bom-comp-select');
        const unitInp = row.querySelector('.bom-unit-input');
        if (!sel || !unitInp) return;
        const opt = sel.options[sel.selectedIndex];
        const u = opt && opt.getAttribute('data-unit');
        if (u) unitInp.value = u;
    }

    document.querySelectorAll('.bom-line-row').forEach(function (row) {
        row.querySelector('.bom-comp-select')?.addEventListener('change', function () { fillUnitFromSelect(row); });
    });

    const body = document.getElementById('bom-lines-body');
    const tpl = document.getElementById('bom-line-select-options');
    const addBtn = document.getElementById('bom-add-line');

    function bindRow(row) {
        row.querySelector('.bom-remove-line')?.addEventListener('click', function () {
            if (body.querySelectorAll('.bom-line-row').length <= 1) return;
            row.remove();
        });
        row.querySelector('.bom-comp-select')?.addEventListener('change', function () { fillUnitFromSelect(row); });
    }
    document.querySelectorAll('.bom-line-row').forEach(bindRow);

    addBtn?.addEventListener('click', function () {
        const idx = body.querySelectorAll('.bom-line-row').length;
        const tr = document.createElement('tr');
        tr.className = 'bom-line-row border-b border-gray-100 align-top';
        tr.innerHTML =
            '<td class="py-2 px-2"><select name="lines[' + idx + '][component_item_id]" class="bom-comp-select w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500" required>' +
            tpl.innerHTML + '</select></td>' +
            '<td class="py-2 px-2"><input type="number" name="lines[' + idx + '][quantity]" min="0.0001" step="any" required class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500"></td>' +
            '<td class="py-2 px-2"><input type="text" name="lines[' + idx + '][unit]" class="bom-unit-input w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500" placeholder="—"></td>' +
            '<td class="py-2 px-2"><input type="number" name="lines[' + idx + '][scrap_percent]" value="0" min="0" max="100" step="0.01" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500"></td>' +
            '<td class="py-2 px-2"><input type="text" name="lines[' + idx + '][notes]" maxlength="500" class="w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500"></td>' +
            '<td class="py-2 px-2 text-center"><button type="button" class="bom-remove-line text-red-600 hover:text-red-800 text-sm font-semibold px-1">حذف</button></td>';
        body.appendChild(tr);
        bindRow(tr);
    });
})();
</script>
@endpush
@endsection
