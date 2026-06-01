@extends('layouts.app')

@section('title', 'إشعارات المديونية - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">إشعارات المديونية</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">إشعارات المديونية</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة إشعارات مديونية الموردين</p>
        </div>
        <a href="{{ route('finance.debit-notes.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            <span class="text-base leading-none">+</span>
            إشعار مديونية جديد
        </a>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-4">
            <h2 class="inline-flex items-center gap-2 text-xl font-bold text-gray-900">قائمة إشعارات المديونية</h2>
        </div>

        <div class="space-y-4 p-4">
            <div class="flex justify-end">
                <a href="{{ route('finance.debit-notes.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">تحديث</a>
            </div>

            <form method="GET" action="{{ route('finance.debit-notes.index') }}" class="flex flex-row flex-nowrap items-end gap-3">
                <div class="min-w-0 flex-1 space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>البحث في إشعارات المديونية</span>
                        <x-info field="debit_note_search" />
                    </label>
                    <div class="relative">
                        <input type="search" name="search" value="{{ $search }}" placeholder="رقم الإشعار / المورد / المرجع" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-10 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="w-40 shrink-0 space-y-1 sm:w-56">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>الحالة</span>
                        <x-info field="debit_status" />
                    </label>
                    @php
                        $debitNoteStatusOpts = [
                            ['value' => '', 'label' => 'الكل'],
                            ['value' => 'draft', 'label' => 'مسودة'],
                            ['value' => 'approved', 'label' => 'معتمد'],
                            ['value' => 'cancelled', 'label' => 'ملغى'],
                        ];
                    @endphp
                    <x-custom-select
                        name="status"
                        class="w-full"
                        :options="$debitNoteStatusOpts"
                        :selected="$status"
                        :empty-option="false"
                        placeholder="الحالة..."
                    />
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">رقم الإشعار <x-info field="debit_note_number" /></th>
                            <th class="px-4 py-3 text-right">المورد <x-info field="debit_note_supplier" /></th>
                            <th class="px-4 py-3 text-right">التاريخ <x-info field="debit_note_date" /></th>
                            <th class="px-4 py-3 text-right">المبلغ الإجمالي <x-info field="debit_note_total" /></th>
                            <th class="px-4 py-3 text-right">الحالة <x-info field="debit_status" /></th>
                            <th class="w-[1%] whitespace-nowrap px-4 py-3 text-center"><span class="inline-flex items-center justify-center gap-1"><x-info field="debit_note_actions" /> إجراءات</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($debitNotes as $note)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $note->note_number }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $note->supplier->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ optional($note->date)->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-800">{{ number_format((float) $note->amount + (float) $note->tax_amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    @if($note->status === 'approved')
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">معتمد</span>
                                    @elseif($note->status === 'cancelled')
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">ملغى</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">مسودة</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    @php $dnMenuId = 'debit-note-actions-'.$note->id; @endphp
                                    <x-erp-actions-dropdown :menu-id="$dnMenuId">
                                        @if($note->status === 'draft')
                                            <form method="POST" action="{{ route('finance.debit-notes.approve', $note) }}" class="m-0" onsubmit="return confirm('هل أنت متأكد من اعتماد هذا الإشعار؟ سيتم توليد قيد محاسبي وتحديث مديونية المورد فوراً.');">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-emerald-800 transition hover:bg-emerald-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">اعتماد الإشعار</span>
                                                </button>
                                            </form>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <a href="{{ route('finance.debit-notes.edit', $note) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-9.5 9.5a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2L3 10.207V12h1.793L13 3.793z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">تعديل الإشعار</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.debit-notes.destroy', $note) }}" class="m-0" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإشعار؟ لا يمكن التراجع عن هذه الخطوة');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/><path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">حذف الإشعار</span>
                                                </button>
                                            </form>
                                        @elseif($note->status === 'approved')
                                            <a href="{{ route('finance.debit-notes.show', $note) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.252-.264.365A13.133 13.133 0 0 1 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">عرض الإشعار</span>
                                            </a>
                                            <div class="mx-2 my-2 border-t border-gray-100"></div>
                                            <form method="POST" action="{{ route('finance.debit-notes.cancel', $note) }}" class="m-0" onsubmit="return confirm('هل تريد إلغاء الإشعار المعتمد؟ سيتم عكس القيد المحاسبي.');">
                                                @csrf
                                                <button type="submit"
                                                        class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                        role="menuitem">
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.146 2.146a.5.5 0 0 1 .708 0L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854a.5.5 0 0 1 0-.708z"/></svg>
                                                    </span>
                                                    <span class="flex-1 leading-snug">إلغاء الإشعار</span>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('finance.debit-notes.show', $note) }}"
                                               class="erp-menu-item flex items-center gap-3 px-3 py-2.5 text-sm text-gray-800 transition hover:bg-gray-50"
                                               role="menuitem">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.086.13-.17.252-.264.365A13.133 13.133 0 0 1 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/></svg>
                                                </span>
                                                <span class="flex-1 text-right font-medium leading-snug">عرض الإشعار</span>
                                            </a>
                                        @endif
                                    </x-erp-actions-dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-20 text-center text-sm text-gray-500">لا توجد إشعارات مديونية</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($debitNotes->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $debitNotes->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

