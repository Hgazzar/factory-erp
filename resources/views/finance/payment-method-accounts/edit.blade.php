@extends(niche_shell_layout())

@section('title', niche_label('finance.payment_method_accounts', 'ربط طرق الدفع').' - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">وسائل الدفع</span>
@endsection

@section('content')
@php
    $optList = collect($options ?? [])->values()->all();
    $cashId = old('ledger_cash', optional($rows->get(\App\Models\PaymentMethodAccount::KEY_CASH))->ledger_account_id);
    $transferId = old('ledger_transfer', optional($rows->get(\App\Models\PaymentMethodAccount::KEY_TRANSFER))->ledger_account_id);
    $cardId = old('ledger_card', optional($rows->get(\App\Models\PaymentMethodAccount::KEY_CARD))->ledger_account_id);
@endphp
<div dir="rtl" class="mx-auto w-full max-w-3xl space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <header class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <h1 class="inline-flex items-center gap-2 text-2xl font-bold text-gray-900">
            <span>ربط وسائل الدفع بالدليل</span>
            <x-info field="finance.payment_method_accounts_intro" />
        </h1>
        <p class="mt-2 text-sm text-gray-600">أصول متداولة — نقدية وما في حكمها (صندوق، بنك، حسابات بنك فرعية).</p>
    </header>

    <form method="POST" action="{{ route('finance.payment-method-accounts.update') }}" class="space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.payment_method_ledger_cash" /> {{ $labels[\App\Models\PaymentMethodAccount::KEY_CASH] ?? 'نقدي' }} <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="ledger_cash"
                    id="ledger_cash"
                    :options="$optList"
                    :value="$cashId"
                    :required="true"
                    :error="$errors->has('ledger_cash')"
                    empty-label="اختر حساباً"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_cash')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.payment_method_ledger_transfer" /> {{ $labels[\App\Models\PaymentMethodAccount::KEY_TRANSFER] ?? 'تحويل' }} <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="ledger_transfer"
                    id="ledger_transfer"
                    :options="$optList"
                    :value="$transferId"
                    :required="true"
                    :error="$errors->has('ledger_transfer')"
                    empty-label="اختر حساباً"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_transfer')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700"><x-info field="finance.payment_method_ledger_card" /> {{ $labels[\App\Models\PaymentMethodAccount::KEY_CARD] ?? 'شبكة' }} <span class="text-red-500">*</span></label>
                <x-searchable-select
                    name="ledger_card"
                    id="ledger_card"
                    :options="$optList"
                    :value="$cardId"
                    :required="true"
                    :error="$errors->has('ledger_card')"
                    empty-label="اختر حساباً"
                    placeholder="ابحث بالرمز أو الاسم..."
                />
                @error('ledger_card')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.dashboard') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">إلغاء</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">حفظ</button>
        </div>
    </form>
</div>
@endsection
