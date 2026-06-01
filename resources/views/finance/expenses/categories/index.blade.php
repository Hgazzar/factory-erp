@extends('layouts.app')

@section('title', 'تصنيفات المصروفات - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">تصنيفات المصروفات</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">تصنيفات المصروفات</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة تصنيفات المصروفات وربط حالة الضريبة والتفعيل</p>
        </div>
        <a href="{{ route('finance.expenses.categories.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
            <span class="text-base leading-none">+</span>
            تصنيف جديد
        </a>
    </header>

    <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('finance.expenses.categories.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="relative min-w-[220px] flex-1">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="search" name="search" value="{{ $search }}" placeholder="البحث في التصنيفات..." class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pr-10 pl-3 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="h-10 rounded-lg border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">بحث</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_code" /> الرمز</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_name_en" /> الاسم</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_name_ar" /> الاسم بالعربية</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_parent" /> التصنيف الأب</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_taxable" /> خاضع للضريبة</th>
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_status" /> الحالة</th>
                        <th class="w-[1%] whitespace-nowrap px-4 py-3 text-center font-semibold"><span class="inline-flex items-center justify-center gap-1"><x-info field="expense_category_col_actions" /> إجراءات</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $category->code }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $category->name_en ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $category->name_ar }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $category->parent?->name_ar ?: '-' }}</td>
                            <td class="px-4 py-3">
                                @if($category->is_taxable)
                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">نعم</span>
                                @else
                                    <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700">لا</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($category->status === 'active')
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">نشط</span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">غير نشط</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center align-middle">
                                @php $ecMenuId = 'expense-cat-actions-'.$category->id; @endphp
                                <x-erp-actions-dropdown :menu-id="$ecMenuId">
                                    <a href="{{ route('finance.expenses.categories.edit', $category) }}"
                                       class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                       role="menuitem">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                        </span>
                                        <span class="flex-1 text-right font-medium leading-snug">تعديل</span>
                                    </a>
                                    <div class="mx-2 my-2 border-t border-gray-100"></div>
                                    <form method="POST" action="{{ route('finance.expenses.categories.destroy', $category) }}" class="m-0" onsubmit="return confirm('حذف هذا التصنيف؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                role="menuitem">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">حذف</span>
                                        </button>
                                    </form>
                                </x-erp-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">لا توجد تصنيفات مصروفات حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 bg-white px-4 py-3">
            {{ $categories->links() }}
        </div>
    </section>
</div>
@endsection
