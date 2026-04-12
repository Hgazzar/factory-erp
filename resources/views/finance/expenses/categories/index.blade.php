@extends('layouts.app')

@section('title', 'تصنيفات المصروفات - MIRADA ERP')

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
                        <th class="px-4 py-3 text-right font-semibold"><x-info field="expense_category_col_actions" /> إجراءات</th>
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
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="{{ route('finance.expenses.categories.edit', $category) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">تعديل</a>
                                    <form method="POST" action="{{ route('finance.expenses.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('حذف هذا التصنيف؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">حذف</button>
                                    </form>
                                </div>
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
