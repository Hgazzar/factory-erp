@extends('layouts.app')

@section('title', 'قوائم المواد - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('manufacturing.dashboard') }}" class="text-gray-500 hover:text-indigo-600">التصنيع</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">قوائم المواد</span>
@endsection

@section('content')
<div class="max-w-full bg-gray-50 min-h-[calc(100vh-8rem)] -mx-4 sm:-mx-6 px-4 sm:px-6 py-6" dir="rtl">
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-6 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 inline-flex items-center gap-2">
                قوائم المواد
                <x-info field="manufacturing.bom_lists_index_intro" />
            </h1>
            <p class="text-sm text-gray-500 mt-1">قوائم مواد (BOM) مستقلة لكل منتج تام مع إصدار وحالة</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('manufacturing.dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">لوحة التحكم</a>
            <a href="{{ route('manufacturing.bom-lists.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold shadow-sm hover:bg-blue-700">+ قائمة مواد جديدة</a>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الاسم <x-info field="manufacturing.bom_list_col_name" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">المنتج <x-info field="manufacturing.bom_list_col_product" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الإصدار <x-info field="manufacturing.bom_list_col_version" /></span></th>
                        <th class="py-3 px-4 font-semibold"><span class="inline-flex items-center gap-1">الحالة <x-info field="manufacturing.bom_list_col_status" /></span></th>
                        <th class="py-3 px-4 font-semibold"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lists as $list)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $list->name }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $list->finishedItem?->code }} — {{ $list->finishedItem?->name_ar }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $list->version }}</td>
                            <td class="py-3 px-4">
                                @php $labels = \App\Models\BomList::statusLabels(); @endphp
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if($list->status === \App\Models\BomList::STATUS_ACTIVE) bg-emerald-100 text-emerald-800
                                    @elseif($list->status === \App\Models\BomList::STATUS_OBSOLETE) bg-gray-200 text-gray-700
                                    @else bg-amber-100 text-amber-900 @endif">
                                    {{ $labels[$list->status] ?? $list->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <a href="{{ route('manufacturing.bom-lists.show', $list) }}" class="text-blue-600 hover:text-blue-800 font-semibold">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 px-4 text-center text-gray-500">لا توجد قوائم مواد بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lists->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $lists->links() }}</div>
        @endif
    </div>
</div>
@endsection
