@extends('layouts.app')

@section('title', 'سند قبض جديد - ' . config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.receipts.index') }}" class="text-gray-500 hover:text-indigo-600">سندات القبض</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">سند جديد</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">سند قبض</h1>
            <p class="mt-1 text-sm text-gray-500">تحصيل مبالغ من العملاء وتسجيلها في الحسابات.</p>
        </div>
        <a href="{{ route('finance.receipts.index') }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
            الرجوع لسندات القبض
        </a>
    </header>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @if(session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('finance.receipts.store') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">العميل <span class="text-red-500">*</span></label>
                    <select name="customer_id" required
                            class="h-10 w-full rounded-lg border px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('customer_id') border-red-500 @else border-gray-200 bg-gray-50 @enderror">
                        <option value="">— اختر العميل —</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ $customer->code }} — {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-gray-700">التاريخ</label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                           class="h-10 w-full rounded-lg border px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('date') border-red-500 @else border-gray-200 bg-gray-50 @enderror">
                    @error('date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-3">
                    <label class="mb-1 block text-sm font-medium text-gray-700">المبلغ</label>
                    <input type="number" inputmode="decimal" name="amount" value="{{ old('amount', 0) }}" min="0.01" step="any" required
                           class="h-10 w-full rounded-lg border px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('amount') border-red-500 @else border-gray-200 bg-gray-50 @enderror">
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">المرجع</label>
                    <input type="text" name="reference" value="{{ old('reference') }}" maxlength="50"
                           class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500 @error('reference') border-red-500 @enderror">
                    @error('reference')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ route('finance.receipts.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ السند</button>
            </div>
        </form>
    </div>
</div>
@endsection
