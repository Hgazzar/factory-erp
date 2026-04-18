@extends('layouts.app')

@section('title', 'القيود اليومية - UFUQ ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">لوحة المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">القيود اليومية</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full">

    {{-- 1. الهيدر المظبوط: العنوان يمين والزرار شمال --}}
    <header class="flex w-full items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-900">القيود اليومية</h1>
        <a href="{{ route('finance.journals.create') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ قيد جديد</a>
    </header>

    {{-- 2. الفلاتر بنظام Grid حقيقي --}}
    <div class="w-full rounded-lg border border-gray-200 bg-white p-4 shadow-sm mb-6">
        <form method="GET" action="{{ route('finance.journals.index') }}" class="space-y-4">
            {{-- السطر الأول: البحث + نوع القيد + زر البحث في سطر واحد — flex-nowrap --}}
            <div class="flex w-full flex-nowrap items-center gap-3">
                <div class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="البحث في القيود..." class="h-9 w-full rounded-md border border-gray-200 bg-gray-50 text-sm pr-10 pl-3 focus:ring-blue-500">
                </div>
                <div class="shrink-0 w-40">
                    <select name="type" class="h-9 w-full rounded-md border border-gray-200 bg-gray-50 text-sm focus:ring-blue-500">
                        <option value="">الكل</option>
                        <option value="normal" {{ request('type') === 'normal' ? 'selected' : '' }}>قيد عادي</option>
                    </select>
                </div>
                <div class="shrink-0">
                    <button type="submit" class="h-9 rounded-md border border-gray-200 bg-gray-50 px-4 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:ring-blue-500">بحث</button>
                </div>
            </div>
            {{-- السطر الثاني: التواريخ بشكل ملموم على اليمين --}}
            <div class="flex items-center gap-4 border-t border-gray-50 pt-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">من:</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="h-9 w-36 rounded-md border border-gray-200 bg-gray-50 text-sm">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">إلى:</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="h-9 w-36 rounded-md border border-gray-200 bg-gray-50 text-sm">
                </div>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Table: w-full، حدود أفقية فقط، text-sm --}}
    <div class="mt-2 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-full table-auto border-collapse text-sm" role="grid">
                <thead>
                    <tr class="bg-gray-100">
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-right font-medium text-gray-900">رقم القيد</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-right font-medium text-gray-900">التاريخ</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-right font-medium text-gray-900">نوع القيد</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-right font-medium text-gray-900">الوصف</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-left font-medium text-gray-900">مدين</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-left font-medium text-gray-900">دائن</th>
                        <th scope="col" class="border-b border-gray-100 px-3 py-3 text-center font-medium text-gray-900">الحالة</th>
                        <th scope="col" class="w-[1%] whitespace-nowrap border-b border-gray-100 px-3 py-3 text-center font-medium text-gray-900"><span class="inline-flex items-center justify-center gap-1"><x-info field="finance.journal_list_actions" /> إجراءات</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="px-3 py-3 text-right text-gray-900">{{ $entry->id }}</td>
                            <td class="px-3 py-3 text-right text-gray-700">{{ $entry->date?->format('Y-m-d') }}</td>
                            <td class="px-3 py-3 text-right text-gray-700">قيد عادي</td>
                            <td class="max-w-[280px] truncate px-3 py-3 text-right text-gray-700" title="{{ $entry->description ?? $entry->reference ?? '—' }}">{{ $entry->description ?: ($entry->reference ?: '—') }}</td>
                            <td class="px-3 py-3 text-left text-gray-900">{{ number_format((float) $entry->total, 2) }}</td>
                            <td class="px-3 py-3 text-left text-gray-900">{{ number_format((float) $entry->total, 2) }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-sm font-medium text-emerald-700">مسجل</span>
                            </td>
                            <td class="px-3 py-3 text-center align-middle">
                                @php $journalMenuId = 'journal-actions-'.$entry->id; @endphp
                                <x-erp-actions-dropdown :menu-id="$journalMenuId">
                                    <a href="{{ route('finance.journals.edit', $entry) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">تعديل القيد</span>
                                    </a>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form method="POST" action="{{ route('finance.journals.destroy', $entry) }}" class="m-0" onsubmit="return confirm('هل تريد حذف هذا القيد؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف القيد</span>
                                        </button>
                                    </form>
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="border-t border-gray-100 px-4 py-2">{{ $entries->links() }}</div>
        @endif
    </div>

</div>
@endsection
