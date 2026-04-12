@extends('layouts.app')

@section('title', 'الأصول الثابتة - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">الأصول الثابتة</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900">الأصول الثابتة <x-info field="book_value" /></h1>
                <p class="mt-1 text-sm text-gray-500">إدارة الأصول المالية والممتلكات</p>
            </div>
            <a href="{{ route('finance.fixed-assets.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <span class="text-base leading-none">+</span>
                أصل جديد
            </a>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.fixed-assets.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="w-44">
                <select name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">الكل</option>
                    <option value="in_use" @selected($status === 'in_use')>مستخدم</option>
                    <option value="stopped" @selected($status === 'stopped')>متوقف</option>
                    <option value="decommissioned" @selected($status === 'decommissioned')>خارج الخدمة</option>
                </select>
            </div>
            <div class="relative min-w-[240px] flex-1">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="search" name="search" value="{{ $search }}" placeholder="البحث في الأصول..." class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pr-10 pl-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="h-10 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">بحث</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">رمز الأصل</th>
                        <th class="px-4 py-3 text-right">الاسم</th>
                        <th class="px-4 py-3 text-right">التصنيف</th>
                        <th class="px-4 py-3 text-right">مركز التكلفة <x-info field="cost_center" /></th>
                        <th class="px-4 py-3 text-right">تاريخ الاقتناء</th>
                        <th class="px-4 py-3 text-right">تكلفة الاقتناء</th>
                        <th class="px-4 py-3 text-right">القيمة الدفترية <x-info field="book_value" /></th>
                        <th class="px-4 py-3 text-right">الحالة</th>
                        <th class="px-4 py-3 text-right">العمليات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($assets as $asset)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $asset->asset_code }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $asset->name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $asset->categoryRef?->name_ar ?? $asset->category ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $asset->costCenter?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ optional($asset->acquisition_date)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ number_format((float) $asset->acquisition_cost, 0) }} SAR</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ number_format((float) $asset->calculated_book_value, 0) }} SAR</td>
                            <td class="px-4 py-3">
                                @if($asset->status === 'in_use')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">مستخدم</span>
                                @elseif($asset->status === 'stopped')
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">متوقف</span>
                                @else
                                    <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">خارج الخدمة</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('finance.fixed-assets.edit', $asset) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100" title="تعديل">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10L3 14l.146-2.854zM11.207 2 4 9.207V12h2.793L14 4.793z"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('finance.fixed-assets.destroy', $asset) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الأصل؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-red-50 text-red-600 hover:bg-red-100" title="حذف">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0A.5.5 0 0 1 8.5 6v6a.5.5 0 0 1-1 0V6A.5.5 0 0 1 8 5.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3V2h11v1z"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $assets->links() }}
        </div>
    </section>
</div>
@endsection
