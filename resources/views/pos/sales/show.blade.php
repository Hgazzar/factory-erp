@extends('layouts.pos')

@section('title', 'إيصال '.$posSale->receipt_number.' - '.config('app.name'))

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6 space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('pos.receipts.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">← الإيصالات</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 flex flex-col lg:flex-row flex-wrap justify-between gap-6 border-b border-gray-100">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $posSale->receipt_number }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $posSale->created_at?->format('Y-m-d H:i') }}</p>
            </div>
            <div class="text-start lg:text-end">
                <div class="text-2xl font-bold text-gray-900 tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $posSale->total_price, 2) }}</div>
                <div class="text-sm text-gray-500 mt-1 inline-flex items-center gap-1 flex-wrap justify-end">
                    <span class="inline-flex items-center gap-1">طريقة الدفع <x-info field="pos.col_payment_method" />:</span>
                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">{{ $posSale->payment_method }}</span>
                </div>
            </div>
        </div>
        <div class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
            <div>
                <div class="text-xs font-semibold text-gray-500 mb-1 inline-flex items-center gap-1">جهاز نقطة البيع <x-info field="pos.col_device" /></div>
                <div class="text-gray-900 font-medium">{{ $posSale->posDevice?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 mb-1">المستودع</div>
                <div class="text-gray-900 font-medium">{{ $posSale->posDevice?->warehouse?->name_ar ?? $posSale->posDevice?->warehouse?->name_en ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500 mb-1">الجلسة</div>
                <div class="text-gray-900 font-medium">{{ $posSale->posSession ? '#'.$posSale->pos_session_id : '—' }}</div>
            </div>
            @if($posSale->journalEntry)
                <div class="sm:col-span-2 lg:col-span-3">
                    <div class="text-xs font-semibold text-gray-500 mb-1">القيد المحاسبي</div>
                    <a href="{{ route('finance.journals.show', $posSale->journalEntry) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                        {{ $posSale->journalEntry->reference ?? 'عرض القيد' }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-900 inline-flex items-center gap-2">
                بنود الإيصال
                <x-info field="pos.receipt_lines_heading" />
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right min-w-[720px]">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الصنف <x-info field="pos.receipt_col_item" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الكمية <x-info field="pos.receipt_col_qty" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">سعر الوحدة <x-info field="pos.receipt_col_unit_price" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap text-gray-500"><span class="inline-flex items-center gap-1">ت. الوحدة <x-info field="pos.receipt_col_unit_cost" /></span></th>
                        <th class="py-3 px-4 font-semibold whitespace-nowrap"><span class="inline-flex items-center gap-1">الإجمالي <x-info field="pos.receipt_col_line_total" /></span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($posSale->lines as $line)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="py-3 px-4">
                                <span class="font-medium text-gray-900">{{ $line->item?->name_ar ?? $line->item?->name_en ?? $line->item?->code }}</span>
                                @if($line->item?->code)
                                    <span class="text-gray-500 text-xs block">{{ $line->item->code }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 tabular-nums">{{ rtrim(rtrim(number_format((float) $line->quantity, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                            <td class="py-3 px-4 tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $line->unit_price, 2) }}</td>
                            <td class="py-3 px-4 tabular-nums text-gray-500 text-xs">{{ $erpCurrencyCode }} {{ number_format((float) $line->unit_cost, 4) }}</td>
                            <td class="py-3 px-4 tabular-nums font-semibold text-gray-900">{{ $erpCurrencyCode }} {{ number_format((float) $line->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
