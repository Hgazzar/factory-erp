@extends('layouts.app')

@section('title', 'أوامر التصنيع - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-indigo-600">التصنيع</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">جميع الأوامر</span>
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
                جميع الأوامر
                <x-info field="manufacturing.list_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">مسودة ثم ترحيل: المخزون عبر خدمة المخزون الموحّدة، والقيد عبر المحاسبة مع تحديث الرصيد التراكمي للحسابات</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('manufacturing.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">لوحة التحكم</a>
            <a href="{{ route('manufacturing.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-medium shadow-sm hover:bg-blue-700">أمر جديد</a>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">المرجع <x-info field="manufacturing.col_reference" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">التاريخ <x-info field="manufacturing.col_date" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الحالة <x-info field="manufacturing.col_status" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">المنتج التام <x-info field="manufacturing.col_finished" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">الكمية <x-info field="manufacturing.col_qty" /></span></th>
                        <th class="py-3 px-4 font-medium"><span class="inline-flex items-center gap-1">تكلفة المواد <x-info field="manufacturing.col_material_cost" /></span></th>
                        <th class="py-3 px-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr class="border-b border-gray-100">
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $run->reference }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $run->production_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if($run->status === \App\Models\ManufacturingRun::STATUS_POSTED)
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">مرحّل</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">مسودة</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-700">{{ $run->finishedItem?->code }} — {{ $run->finishedItem?->name_ar }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ rtrim(rtrim(number_format((float) $run->quantity_produced, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                            <td class="py-3 px-4 text-gray-700">
                                @if($run->total_materials_cost !== null)
                                    {{ number_format((float) $run->total_materials_cost, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('manufacturing.show', $run) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 px-4 text-center text-gray-500">لا توجد أوامر بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($runs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $runs->links() }}</div>
        @endif
    </div>
</div>
@endsection
