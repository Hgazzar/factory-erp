@extends('layouts.app')

@section('title', 'مراكز التكلفة - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">مراكز التكلفة</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900">مراكز التكلفة <x-info field="cost_center" /></h1>
                <p class="mt-1 text-sm text-gray-500">إدارة مراكز التكلفة لتوزيع المصروفات</p>
            </div>
            <a href="{{ route('finance.cost-centers.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <span class="text-base leading-none">+</span>
                مركز تكلفة جديد
            </a>
        </div>
    </section>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.cost-centers.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[260px] flex-1">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="search" name="search" value="{{ $search }}" placeholder="البحث في مراكز التكلفة..." class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pr-10 pl-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="h-10 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">بحث</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-sm">
                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-right">الرمز</th>
                        <th class="px-4 py-3 text-right">الاسم</th>
                        <th class="px-4 py-3 text-right">الفرع</th>
                        <th class="px-4 py-3 text-right">الميزانية السنوية <x-info field="annual_budget" /></th>
                        <th class="px-4 py-3 text-right">المصروف <x-info field="spent_amount" /></th>
                        <th class="px-4 py-3 text-right">الحالة</th>
                        <th class="px-4 py-3 text-right">العمليات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($centers as $center)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $center->code }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $center->name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $center->branch }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ erp_money($center->annual_budget) }} SAR</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-800">{{ erp_money($center->spent_amount) }} SAR</td>
                            <td class="px-4 py-3">
                                @if($center->status === 'active')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">نشط</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">غير نشط</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('finance.cost-centers.edit', $center) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100" title="تعديل">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10L3 14l.146-2.854zM11.207 2 4 9.207V12h2.793L14 4.793z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 px-4 py-3">
            {{ $centers->links() }}
        </div>
    </section>
</div>
@endsection
