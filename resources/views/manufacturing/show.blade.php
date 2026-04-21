@extends('layouts.app')

@section('title', ($run->reference ?? 'أمر عمل').' - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-indigo-600">لوحة التحكم</a>
    <span>›</span>
    <a href="{{ route('manufacturing.runs.index') }}" class="text-gray-500 hover:text-indigo-600">جميع الأوامر</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">{{ $run->reference }}</span>
@endsection

@section('content')
<div class="max-w-full" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-6 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                {{ $run->reference }}
                <x-info field="manufacturing.show_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                @if($run->isPosted())
                    تم الترحيل محاسبياً ومخزنياً.
                @else
                    مسودة — الترحيل ينفّذ صرف المدخلات وإضافة التام والقيد.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('manufacturing.runs.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">جميع الأوامر</a>
            @if($run->isDraft())
                <form method="POST" action="{{ route('manufacturing.post', $run) }}" class="inline" onsubmit="return confirm('تأكيد الترحيل؟ سيتم صرف المدخلات وإضافة المنتج التام وتسجيل القيد وتحديث أرصدة الحسابات.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #16a34a;">ترحيل</button>
                </form>
                <form method="POST" action="{{ route('manufacturing.destroy', $run) }}" class="inline" onsubmit="return confirm('حذف هذه المسودة؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm font-medium hover:bg-red-100">حذف المسودة</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
            <h2 class="font-semibold text-gray-900 mb-2 inline-flex items-center gap-1">البيانات الأساسية <x-info field="manufacturing.show_header" /></h2>
            @if($run->bomList)
                <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                    <span class="text-gray-500 inline-flex items-center gap-1">قائمة المواد <x-info field="manufacturing.wo_field_bom_list" /></span>
                    <span class="font-medium text-gray-900">{{ $run->bomList->name }} — {{ $run->bomList->version }}</span>
                </div>
            @endif
            <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                <span class="text-gray-500 inline-flex items-center gap-1">تاريخ المخزون والقيد <x-info field="manufacturing.field_production_date" /></span>
                <span class="font-medium text-gray-900">{{ $run->production_date?->format('Y-m-d') }}</span>
            </div>
            @if($run->start_date)
                <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                    <span class="text-gray-500 inline-flex items-center gap-1">تاريخ البدء <x-info field="manufacturing.wo_field_start_date" /></span>
                    <span class="font-medium text-gray-900">{{ $run->start_date->format('Y-m-d') }}</span>
                </div>
            @endif
            @if($run->due_date)
                <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                    <span class="text-gray-500 inline-flex items-center gap-1">تاريخ الاستحقاق <x-info field="manufacturing.wo_field_due_date" /></span>
                    <span class="font-medium text-gray-900">{{ $run->due_date->format('Y-m-d') }}</span>
                </div>
            @endif
            <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                <span class="text-gray-500 inline-flex items-center gap-1">المستودع <x-info field="manufacturing.field_warehouse" /></span>
                <span class="font-medium text-gray-900">{{ $run->warehouse?->name_ar }}</span>
            </div>
            @if($run->machine)
                <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                    <span class="text-gray-500 inline-flex items-center gap-1">الماكينة <x-info field="manufacturing.wo_field_machine" /></span>
                    <span class="font-medium text-gray-900">{{ $run->machine->code }} — {{ $run->machine->name_ar }}</span>
                </div>
            @endif
            <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                <span class="text-gray-500 inline-flex items-center gap-1">المنتج التام <x-info field="manufacturing.field_finished_item" /></span>
                <span class="font-medium text-gray-900">{{ $run->finishedItem?->code }} — {{ $run->finishedItem?->name_ar }}</span>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                <span class="text-gray-500 inline-flex items-center gap-1">كمية الإنتاج <x-info field="manufacturing.field_qty_produced" /></span>
                <span class="font-medium text-gray-900">{{ rtrim(rtrim(number_format((float) $run->quantity_produced, 4, '.', ''), '0'), '.') ?: '0' }}</span>
            </div>
            @if($run->total_materials_cost !== null)
                <div class="flex justify-between gap-4 border-b border-gray-100 py-2">
                    <span class="text-gray-500 inline-flex items-center gap-1">تكلفة المواد <x-info field="manufacturing.col_material_cost" /></span>
                    <span class="font-medium text-gray-900">{{ number_format((float) $run->total_materials_cost, 2) }}</span>
                </div>
            @endif
            @if($run->journalEntry)
                <div class="flex justify-between gap-4 py-2">
                    <span class="text-gray-500 inline-flex items-center gap-1">القيد <x-info field="manufacturing.journal_link" /></span>
                    <a href="{{ route('finance.journals.show', $run->journalEntry) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">عرض القيد #{{ $run->journalEntry->id }}</a>
                </div>
            @endif
            @if($run->notes)
                <p class="text-gray-600 pt-2"><span class="font-medium text-gray-800 inline-flex items-center gap-1">ملاحظات <x-info field="manufacturing.field_notes" /></span> {{ $run->notes }}</p>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 font-semibold text-gray-800 inline-flex items-center gap-1">
                بنود المدخلات
                <x-info field="manufacturing.lines_table" />
            </div>
            <div class="overflow-x-auto p-4">
                <table class="w-full text-sm text-right">
                    <thead class="text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">الصنف <x-info field="manufacturing.line_ingredient" /></span></th>
                            <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">كمية مخططة <x-info field="manufacturing.wo_col_planned_qty" /></span></th>
                            <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">هدر مخطط % <x-info field="manufacturing.wo_col_planned_scrap" /></span></th>
                            <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">كمية فعلية <x-info field="manufacturing.wo_col_actual_qty" /></span></th>
                            <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">هدر فعلي % <x-info field="manufacturing.wo_col_actual_scrap" /></span></th>
                            @if($run->isPosted())
                                <th class="py-2 px-2 font-medium"><span class="inline-flex items-center gap-1">تكلفة السطر <x-info field="manufacturing.line_cost" /></span></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($run->lines as $line)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 px-2 text-gray-800">{{ $line->ingredientItem?->code }} — {{ $line->ingredientItem?->name_ar }}</td>
                                <td class="py-2 px-2 text-gray-600">{{ $line->planned_quantity !== null ? (rtrim(rtrim(number_format((float) $line->planned_quantity, 4, '.', ''), '0'), '.') ?: '0') : '—' }}</td>
                                <td class="py-2 px-2 text-gray-600">{{ $line->planned_scrap_percent !== null ? (rtrim(rtrim(number_format((float) $line->planned_scrap_percent, 4, '.', ''), '0'), '.') ?: '0') : '—' }}</td>
                                <td class="py-2 px-2 font-medium text-gray-900">{{ rtrim(rtrim(number_format((float) $line->quantity_consumed, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                                <td class="py-2 px-2 text-gray-600">{{ $line->actual_scrap_percent !== null ? (rtrim(rtrim(number_format((float) $line->actual_scrap_percent, 4, '.', ''), '0'), '.') ?: '0') : '—' }}</td>
                                @if($run->isPosted())
                                    <td class="py-2 px-2 text-gray-700">{{ $line->line_cost !== null ? number_format((float) $line->line_cost, 2) : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
