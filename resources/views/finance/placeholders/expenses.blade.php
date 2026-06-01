@extends('layouts.app')

@section('title', 'المصروفات - '.config('app.name'))

@push('styles')
<style>
    .exp-notes-2l {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
</style>
@endpush

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المحاسبة</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">المصروفات</span>
@endsection

@section('content')
@php
    $expenseIdxSupplierOpts = collect($suppliers ?? [])->map(fn ($s) => [
        'value' => $s->id,
        'label' => (string) ($s->localized_display_name ?? $s->name_ar ?? $s->name ?? ''),
    ])->values()->all();
    $expenseIdxAccountOpts = collect($filterExpenseAccounts ?? [])->map(fn ($a) => [
        'value' => $a->id,
        'label' => trim((string) ($a->code ?? '').' — '.(string) ($a->name_ar ?? $a->name_en ?? '')),
    ])->values()->all();
    $expenseIdxCcOpts = collect($filterCostCenters ?? [])->map(fn ($c) => [
        'value' => $c->id,
        'label' => trim((string) ($c->code ?? '').' — '.(string) ($c->name_ar ?? $c->name ?? '')),
    ])->values()->all();
@endphp
<div dir="rtl" class="mx-auto w-full min-w-0 max-w-full">
    <header class="mb-4 flex w-full flex-wrap items-start justify-between gap-3 border-b border-gray-100 pb-3">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.886 0-3.59.553-4.818 1.447M12 8c1.886 0 3.59.553 4.818 1.447M12 8V6m-4.818 3.447A6.97 6.97 0 005 14v1a2 2 0 002 2h10a2 2 0 002-2v-1a6.97 6.97 0 00-2.182-4.553M10 14h4" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">المصروفات</h1>
                <p class="mt-1 text-sm text-gray-500">إدارة طلبات المصروفات والتعويضات</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M8 11l4 4m0 0l4-4m-4 4V3" />
                </svg>
                تصدير
            </button>
            <button type="button" class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" data-bs-toggle="modal" data-bs-target="#expensesImportModal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7v1a2 2 0 002 2h12a2 2 0 002-2V7M8 13l4-4m0 0l4 4m-4-4v12" />
                </svg>
                استيراد
            </button>
            <a href="{{ route('finance.expenses.create') }}" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">+ مصروف جديد</a>
            @if(! empty($canBulkDeleteAllExpenses))
                <button type="button"
                        class="inline-flex items-center gap-1 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                        data-bs-toggle="modal"
                        data-bs-target="#expensesBulkDeleteModal"
                        @disabled(($expenseSummary['count'] ?? 0) < 1)>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    مسح الكل (حسب الفلاتر)
                </button>
            @endif
        </div>
    </header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            {{ session('error') }}
        </div>
    @endif

    @if (session('import_result'))
        <x-import-summary :result="session('import_result')" />
    @endif

    <section class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500"><x-info field="finance.expense_index_summary_filtered" /> عدد السجلات</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ number_format($expenseSummary['count'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500">مجموع المبلغ (قبل الضريبة)</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ number_format($expenseSummary['sum_amount'] ?? 0, 2) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500"><x-info field="finance.expense_col_tax_amount" /> مجموع الضريبة</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-gray-900">{{ number_format($expenseSummary['sum_tax'] ?? 0, 2) }}</p>
        </div>
        <div class="rounded-lg border border-emerald-100 bg-emerald-50/80 p-4 shadow-sm">
            <p class="text-xs font-medium text-emerald-800"><x-info field="finance.expense_col_total_amount" /> الإجمالي</p>
            <p class="mt-1 text-xl font-bold tabular-nums text-emerald-900">{{ number_format($expenseSummary['sum_grand'] ?? 0, 2) }}</p>
        </div>
    </section>

    <section class="mb-4 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
        <form method="GET" action="{{ route('finance.expenses.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-44">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="expense_index_filter_workflow_status" /> الحالة</label>
                @php
                    $phExpenseStatusOpts = [
                        ['value' => '', 'label' => 'الكل'],
                        ['value' => 'posted', 'label' => 'معتمد'],
                        ['value' => 'draft', 'label' => 'مسودة'],
                    ];
                @endphp
                <x-custom-select
                    name="status"
                    class="w-full"
                    :options="$phExpenseStatusOpts"
                    :selected="$status ?? ''"
                    :empty-option="false"
                    placeholder="الحالة..."
                />
            </div>
            <div class="min-w-0 w-full max-w-[220px] shrink-0 sm:max-w-xs">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="finance.expense_index_filter_supplier" /> المورد</label>
                <x-searchable-select
                    name="supplier_id"
                    id="filter_expenses_supplier_id"
                    :options="$expenseIdxSupplierOpts"
                    :value="request('supplier_id', $supplierId ?? '')"
                    :required="false"
                    empty-label="كل الموردين"
                    placeholder="ابحث عن مورد..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>
            <div class="min-w-0 w-full max-w-[240px] shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="expense_expense_account" /> الحساب</label>
                <x-searchable-select
                    name="expense_account_id"
                    id="filter_expenses_account_id"
                    :options="$expenseIdxAccountOpts"
                    :value="request('expense_account_id', $expenseAccountId ?? '')"
                    :required="false"
                    empty-label="كل الحسابات"
                    placeholder="ابحث بالرمز أو الاسم..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>
            <div class="min-w-0 w-full max-w-[220px] shrink-0">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="cost_center" /> مركز التكلفة</label>
                <x-searchable-select
                    name="cost_center_id"
                    id="filter_expenses_cost_center_id"
                    :options="$expenseIdxCcOpts"
                    :value="request('cost_center_id', $costCenterId ?? '')"
                    :required="false"
                    empty-label="الكل"
                    placeholder="ابحث بمركز التكلفة..."
                    class="[&_button]:h-10 [&_button]:text-sm"
                />
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600"><x-info field="finance.expense_index_filter_date_range" /> من تاريخ</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="w-40">
                <label class="mb-1 block text-xs font-medium text-gray-600">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="relative min-w-[220px] flex-1">
                <label class="mb-1 block text-xs font-medium text-gray-600">بحث</label>
                <span class="pointer-events-none absolute bottom-2.5 right-3 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                    </svg>
                </span>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="البحث في المصروفات..." class="h-10 w-full rounded-md border border-gray-200 bg-gray-50 pr-10 pl-3 text-sm text-gray-700 placeholder:text-gray-400 focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex shrink-0 items-center gap-2 pb-0.5">
                <button type="submit" class="h-10 rounded-md bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">بحث</button>
                <a href="{{ route('finance.expenses.index') }}" class="inline-flex h-10 items-center rounded-md border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50">مسح</a>
            </div>
        </form>
    </section>

    <section class="w-full min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="w-full min-w-0 overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th scope="col" class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">رقم المصروف</th>
                        <th scope="col" class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">التاريخ</th>
                        <th scope="col" class="w-[8.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="finance.expense_col_supplier" /> المورد</th>
                        <th scope="col" class="w-[7rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">التصنيف</th>
                        <th scope="col" class="w-[6.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="finance.expense_col_reference" /> رقم مرجعي</th>
                        <th scope="col" class="min-w-0 border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="finance.expense_col_description" /> الوصف</th>
                        <th scope="col" class="w-[5.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold">المبلغ</th>
                        <th scope="col" class="w-[5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="finance.expense_col_tax_amount" /> الضريبة</th>
                        <th scope="col" class="w-[5.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="finance.expense_col_total_amount" /> الإجمالي</th>
                        <th scope="col" class="w-[5.5rem] border-b border-gray-200 px-3 py-3 text-right font-semibold"><x-info field="expense_col_workflow_status" /> الحالة</th>
                        <th scope="col" class="w-[1%] whitespace-nowrap border-b border-gray-200 px-3 py-3 text-center font-semibold"><x-info field="expense_col_actions" /> إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        @php
                            $tax = (float) ($expense->tax_amount ?? 0);
                            $lineTotal = (float) $expense->amount + $tax;
                            $posted = ($expense->status ?? '') === 'posted' || $expense->journal_entry_id;
                            $u = auth()->user();
                            $isManagerOrAdmin = $u && ($u->isAdminOrSuperAdmin() || $u->role === 'supervisor');
                        @endphp
                        <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50">
                            <td class="px-3 py-3 text-right text-gray-700 whitespace-nowrap">{{ $expense->expense_number ?? ('EXP-'.str_pad((string) $expense->id, 5, '0', STR_PAD_LEFT)) }}</td>
                            <td class="px-3 py-3 text-right text-gray-700 whitespace-nowrap">{{ $expense->date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="min-w-0 px-3 py-3 text-right text-gray-700 break-words leading-snug">{{ $expense->supplier?->localized_display_name ?? '—' }}</td>
                            <td class="min-w-0 px-3 py-3 text-right text-gray-700 break-words leading-snug">{{ $expense->expenseCategory?->name_ar ?? ($expense->expenseAccount?->name_ar ?? '—') }}</td>
                            <td class="min-w-0 px-3 py-3 text-right text-gray-700 break-words">{{ $expense->reference !== null && $expense->reference !== '' ? $expense->reference : '—' }}</td>
                            <td class="min-w-0 px-3 py-3 align-top text-right text-gray-700">
                                @php $notesText = $expense->notes !== null && $expense->notes !== '' ? $expense->notes : '—'; @endphp
                                <p class="exp-notes-2l text-sm leading-snug" title="{{ $notesText !== '—' ? $notesText : '' }}">{{ $notesText }}</p>
                            </td>
                            <td class="px-3 py-3 text-right font-medium text-gray-900 tabular-nums whitespace-nowrap">{{ number_format((float) $expense->amount, 2) }}</td>
                            <td class="px-3 py-3 text-right text-gray-800 tabular-nums whitespace-nowrap">{{ number_format($tax, 2) }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-900 tabular-nums whitespace-nowrap">{{ number_format($lineTotal, 2) }}</td>
                            <td class="px-3 py-3 text-right">
                                @if($posted)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">معتمد</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">مسودة</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center align-middle">
                                @php
                                    $expenseMenuId = 'expense-actions-'.$expense->id;
                                    $showPdf = $posted;
                                    $showApprove = ! $posted && $isManagerOrAdmin;
                                    $showEditDraft = ! $posted;
                                    $showDeleteDraft = ! $posted && \App\Support\ErpRoles::canDeleteExpenseDraft($u);
                                    $showBackToDraftApproved = $posted && \App\Support\ErpRoles::canRevertApprovedExpenseToDraft($u);
                                    $showHardDeleteApproved = $posted && \App\Support\ErpRoles::canHardDeleteApprovedExpense($u);
                                    $showAnyDelete = $showDeleteDraft || $showHardDeleteApproved;
                                @endphp
                                <div class="relative inline-flex items-center justify-center">
                                    <button type="button"
                                            class="erp-actions-trigger inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
                                            data-actions-menu="{{ $expenseMenuId }}"
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            title="المزيد من الإجراءات"
                                            aria-label="المزيد من الإجراءات">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                        </svg>
                                    </button>
                                    <div id="{{ $expenseMenuId }}"
                                         class="erp-actions-menu hidden min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                         style="list-style: none;"
                                         role="menu"
                                         dir="rtl">
                                        @if($showPdf)
                                            {{-- رابط PDF منفصل عن x-info لتجنب تداخل عناصر تفاعلية داخل <a> (تعطيل النقر / تجمّد الواجهة) --}}
                                            <div class="flex w-full min-w-0 items-stretch" role="menuitem">
                                                {{-- نفس التبويب: يُفادي about:blank عند حظر النوافذ المنبثقة؛ عارض PDF مدمج في المتصفح --}}
                                                <a href="{{ route('finance.expenses.pdf', $expense) }}"
                                                   class="erp-menu-item erp-expense-pdf-link flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-right text-sm text-gray-800 transition hover:bg-gray-50"
                                                   onclick="if (window.closeErpActionMenus) window.closeErpActionMenus();">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3h-3A1.5 1.5 0 0 0 5 4.5v7A1.5 1.5 0 0 0 6.5 13h7a1.5 1.5 0 0 0 1.5-1.5v-7z"/><path d="M4.5 12.5A2.5 2.5 0 0 1 2 10V2a2 2 0 0 1 2-2h3.172a2 2 0 0 1 1.414.586l4.828 4.828A2 2 0 0 1 14 4.828V10a2.5 2.5 0 0 1-2.5 2.5h-7z"/></svg>
                                                    </span>
                                                    <span class="min-w-0 flex-1 font-medium leading-snug">معاينة وطباعة PDF</span>
                                                </a>
                                                <div class="flex shrink-0 items-center ps-1 pe-2">
                                                    <x-info field="expense_action_download_pdf" />
                                                </div>
                                            </div>
                                        @endif
                                        @if($showPdf && ($showApprove || $showEditDraft || $showBackToDraftApproved || $showAnyDelete))
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                        @endif
                                        @if($showBackToDraftApproved)
                                            <div class="flex w-full min-w-0 items-stretch" role="menuitem">
                                                <form method="POST" action="{{ route('finance.expenses.back-to-draft', $expense) }}" class="m-0 flex min-w-0 flex-1">
                                                    @csrf
                                                    <button type="submit"
                                                            class="erp-menu-item flex w-full min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-amber-900 transition hover:bg-amber-50"
                                                            onclick="if (window.closeErpActionMenus) window.closeErpActionMenus();">
                                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242 2.656a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/><path d="M8 5H6v4l3 2 .001-6z"/></svg>
                                                        </span>
                                                        <span class="min-w-0 flex-1 font-medium leading-snug">إرجاع إلى مسودة</span>
                                                    </button>
                                                </form>
                                                <div class="flex shrink-0 items-center ps-1 pe-2">
                                                    <x-info field="expense_action_back_to_draft" />
                                                </div>
                                            </div>
                                        @endif
                                        @if($showApprove)
                                            <form method="POST" action="{{ route('finance.expenses.approve', $expense) }}" class="m-0">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-emerald-800 transition hover:bg-emerald-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>
                                                    </span>
                                                    <span class="flex-1 font-medium leading-snug">اعتماد</span>
                                                </button>
                                            </form>
                                        @endif
                                        @if($showApprove && $showEditDraft)
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                        @endif
                                        @if($showEditDraft)
                                            <a href="{{ route('finance.expenses.edit', $expense) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تعديل</span>
                                            </a>
                                        @endif
                                        @if($showAnyDelete)
                                            @if($showApprove || $showEditDraft || $showBackToDraftApproved)
                                                <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            @endif
                                            <button type="button"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteExpenseModalShared"
                                                    data-delete-url="{{ route('finance.expenses.destroy', $expense) }}"
                                                    data-hard-delete="{{ $showHardDeleteApproved ? '1' : '0' }}"
                                                    data-expense-label="{{ e($expense->expense_number ?? ('#'.$expense->id)) }}"
                                                    class="erp-menu-item erp-expense-delete-trigger flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                    role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                </span>
                                                <span class="flex-1 leading-snug">{{ $showHardDeleteApproved ? 'حذف نهائي' : 'حذف' }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-sm text-gray-500">لا توجد بيانات</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $expenses->links() }}
            </div>
        @endif
    </section>

    {{-- مودال حذف واحد خارج الجدول (المودال داخل <td> يكسر DOM وقد يخفي زر التأكيد) --}}
    <div class="modal fade" id="deleteExpenseModalShared" tabindex="-1" aria-labelledby="deleteExpenseModalSharedTitle" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900" id="deleteExpenseModalSharedTitle">تأكيد الحذف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-sm leading-6 text-gray-700" id="deleteExpenseModalSharedBody"></p>
                </div>
                <div class="modal-footer d-flex flex-wrap align-items-center justify-content-between gap-3 border-t border-gray-200">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form id="deleteExpenseModalSharedForm" method="POST" action="#" class="m-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" id="deleteExpenseModalSharedSubmit">تأكيد الحذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(! empty($canBulkDeleteAllExpenses))
        <div class="modal fade" id="expensesBulkDeleteModal" tabindex="-1" aria-labelledby="expensesBulkDeleteModalTitle" aria-hidden="true" dir="rtl">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content rounded-lg border border-red-100">
                    <div class="modal-header border-b border-red-100 bg-red-50/50">
                        <h5 class="modal-title text-base font-bold text-red-900" id="expensesBulkDeleteModalTitle">مسح جميع المصروفات المطابقة للفلاتر</h5>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <form method="POST" action="{{ route('finance.expenses.destroy-all-matching') }}" id="expensesBulkDeleteForm">
                        @csrf
                        <input type="hidden" name="search" value="{{ $search ?? '' }}">
                        <input type="hidden" name="status" value="{{ $status ?? '' }}">
                        <input type="hidden" name="supplier_id" value="{{ $supplierId ?? '' }}">
                        <input type="hidden" name="expense_account_id" value="{{ $expenseAccountId ?? '' }}">
                        <input type="hidden" name="cost_center_id" value="{{ $costCenterId ?? '' }}">
                        <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}">
                        <div class="modal-body space-y-4 text-sm text-gray-800">
                            <p class="leading-relaxed">
                                سيتم <strong class="text-red-800">حذف نهائي</strong> لعدد
                                <strong class="tabular-nums text-gray-900">{{ number_format((int) ($expenseSummary['count'] ?? 0)) }}</strong>
                                مصروف يطابق نفس فلاتر البحث والملخص أعلاه (وليس صفحة الجدول الحالية فقط).
                            </p>
                            <p class="text-xs text-red-800">
                                <x-info field="expense_action_delete_all_matching" />
                            </p>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                                <p class="mb-1 font-medium text-gray-700">الفلاتر المرسلة:</p>
                                <ul class="mb-0 list-disc space-y-0.5 pr-5">
                                    <li>الحالة: {{ $status === 'posted' ? 'معتمد' : ($status === 'draft' ? 'مسودة' : 'الكل') }}</li>
                                    <li>البحث: {{ ($search ?? '') !== '' ? $search : '—' }}</li>
                                </ul>
                            </div>
                            <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/60 p-3">
                                <input type="checkbox" name="confirm_bulk_delete" id="expenses_bulk_delete_confirm" value="1" class="mt-1 h-4 w-4 shrink-0 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <label for="expenses_bulk_delete_confirm" class="cursor-pointer text-sm leading-relaxed text-gray-800">
                                    <span class="inline-flex flex-wrap items-center gap-1">
                                        <x-info field="expense_bulk_delete_confirm_checkbox" />
                                        <span>أفهم أن الحذف لا يمكن التراجع عنه.</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer flex flex-wrap items-center justify-between gap-3 border-t border-gray-200">
                            <button type="button" class="btn btn-outline-secondary rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger rounded-lg">تنفيذ مسح الكل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="expensesImportModal" tabindex="-1" aria-hidden="true" dir="rtl">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2xl">
                <div class="modal-header border-b border-gray-200">
                    <h5 class="modal-title text-base font-semibold text-gray-900">استيراد المصروفات</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('finance.expenses.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body space-y-3 text-sm text-gray-700">
                        <p>ارفع ملف CSV / Excel بنفس ترويسة القالب.</p>
                        <input type="file" name="file" accept=".csv,.txt,.xlsx,.xls" class="block w-full rounded-md border border-gray-200 px-3 py-2 text-sm" required>
                        <a href="{{ route('finance.expenses.import-template') }}" class="inline-flex items-center text-xs font-medium text-indigo-700 hover:text-indigo-900">تحميل قالب الاستيراد</a>
                    </div>
                    <div class="modal-footer border-t border-gray-200">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-primary">استيراد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    var modalEl = document.getElementById('deleteExpenseModalShared');
    if (!modalEl || !(window.bootstrap && window.bootstrap.Modal)) {
        return;
    }

    modalEl.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn || !btn.getAttribute('data-delete-url')) {
            return;
        }
        var url = btn.getAttribute('data-delete-url');
        var hard = btn.getAttribute('data-hard-delete') === '1';
        var label = btn.getAttribute('data-expense-label') || '';

        var form = document.getElementById('deleteExpenseModalSharedForm');
        var titleEl = document.getElementById('deleteExpenseModalSharedTitle');
        var bodyEl = document.getElementById('deleteExpenseModalSharedBody');
        var submitEl = document.getElementById('deleteExpenseModalSharedSubmit');

        if (form) form.setAttribute('action', url);
        if (titleEl) titleEl.textContent = hard ? 'تأكيد الحذف النهائي' : 'تأكيد الحذف';
        if (submitEl) submitEl.textContent = hard ? 'حذف نهائي' : 'تأكيد الحذف';

        if (bodyEl) {
            if (hard) {
                bodyEl.innerHTML = 'هل أنت متأكد من <strong>الحذف النهائي</strong> للمصروف المعتمد رقم <span class="font-semibold">' + escapeHtml(label) + '</span> وسند القيد المرتبط؟ لا يمكن التراجع.';
            } else {
                bodyEl.innerHTML = 'هل أنت متأكد من حذف مسودة المصروف رقم <span class="font-semibold">' + escapeHtml(label) + '</span>؟';
            }
        }

        if (window.closeErpActionMenus) window.closeErpActionMenus();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        var form = document.getElementById('deleteExpenseModalSharedForm');
        if (form) form.setAttribute('action', '#');
    });
})();
</script>
@endpush
