@extends('layouts.app')

@section('title', 'إشعارات الائتمان - MIRADA ERP')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-blue-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-blue-600">المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-blue-900 font-semibold">إشعارات الائتمان</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-full space-y-6">
    <section class="flex flex-wrap items-start justify-between gap-4 rounded-lg bg-white p-4 md:p-5">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">إشعارات الائتمان</h1>
            <p class="mt-1 text-sm text-gray-500">إدارة إشعارات ائتمان العملاء</p>
        </div>
        <a href="{{ route('finance.credit-notes.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            <span class="text-base leading-none">+</span>
            إشعار ائتمان جديد
        </a>
    </section>

    <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-4">
            <h2 class="inline-flex items-center gap-2 text-xl font-bold text-gray-900">
                <span>قائمة إشعارات الائتمان</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h6.5L14 3.5v1zM10.5 1.5V4H13"/>
                    <path d="M8.854 7.146a.5.5 0 1 1 .707.708L8.207 9.207H11.5a.5.5 0 0 1 0 1H8.207l1.354 1.353a.5.5 0 1 1-.707.708l-2.207-2.207a.5.5 0 0 1 0-.708l2.207-2.207z"/>
                </svg>
            </h2>
        </div>

        <div class="space-y-4 p-4">
            <div class="flex justify-end">
                <a href="{{ route('finance.credit-notes.index') }}" class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    تحديث
                </a>
            </div>

            <form method="GET" action="{{ route('finance.credit-notes.index') }}" class="flex flex-row flex-nowrap items-end gap-3">
                <div class="min-w-0 flex-1 space-y-1">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>البحث في إشعارات الائتمان</span>
                        <x-info field="credit_note_search" />
                    </label>
                    <div class="relative">
                        <input type="search" name="search" value="{{ $search }}" placeholder="رقم الإشعار / العميل / المرجع" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-10 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="w-40 shrink-0 space-y-1 sm:w-56">
                    <label class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                        <span>الحالة</span>
                        <x-info field="credit_status" />
                    </label>
                    <select name="status" class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">الكل</option>
                        <option value="draft" @selected($status === 'draft')>مسودة</option>
                        <option value="approved" @selected($status === 'approved')>معتمد</option>
                        <option value="cancelled" @selected($status === 'cancelled')>ملغى</option>
                    </select>
                </div>
            </form>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="w-full min-w-[980px] text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-right">رقم الإشعار <x-info field="credit_note_number" /></th>
                            <th class="px-4 py-3 text-right">العميل <x-info field="credit_note_customer" /></th>
                            <th class="px-4 py-3 text-right">التاريخ <x-info field="credit_note_date" /></th>
                            <th class="px-4 py-3 text-right">المبلغ الإجمالي <x-info field="credit_note_total" /></th>
                            <th class="px-4 py-3 text-right">الحالة <x-info field="credit_status" /></th>
                            <th class="px-4 py-3 text-right">الإجراءات <x-info field="credit_note_actions" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($creditNotes as $note)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $note->note_number }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $note->customer->name_ar ?: $note->customer->name }}</td>
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
                                <td class="px-4 py-3">
                                    <div class="inline-flex items-center gap-1">
                                        @if($note->status === 'draft')
                                            <button type="button"
                                                data-approve-url="{{ route('finance.credit-notes.approve', $note) }}"
                                                data-note-number="{{ $note->note_number }}"
                                                class="js-open-approve-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-green-200 bg-white text-green-600 hover:bg-green-50"
                                                title="اعتماد">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                            <a href="{{ route('finance.credit-notes.edit', $note) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="تعديل">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.586a2 2 0 112.828 2.828L11.828 14.828a4 4 0 01-1.414.943l-3.029 1.01 1.01-3.029a4 4 0 01.943-1.414l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('finance.credit-notes.destroy', $note) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإشعار؟ لا يمكن التراجع عن هذه الخطوة');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="حذف">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @elseif($note->status === 'approved')
                                            <a href="{{ route('finance.credit-notes.show', $note) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="عرض">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('finance.credit-notes.cancel', $note) }}" onsubmit="return confirm('هل تريد إلغاء الإشعار المعتمد؟ سيتم عكس القيد المحاسبي واسترجاع مديونية العميل.');" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-500 hover:bg-red-50 hover:text-red-600" title="إلغاء">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('finance.credit-notes.show', $note) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-blue-600" title="عرض">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-20 text-center text-sm text-gray-500">لا توجد إشعارات ائتمان</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($creditNotes->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">
                {{ $creditNotes->links() }}
            </div>
        @endif
    </section>
    <div id="approveCreditNoteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl" dir="rtl">
            <h3 class="text-lg font-bold text-gray-900">تأكيد الاعتماد</h3>
            <p id="approveCreditNoteText" class="mt-2 text-sm leading-6 text-gray-600">
                هل أنت متأكد من اعتماد هذا الإشعار؟ سيتم توليد قيد محاسبي وتحديث رصيد العميل فوراً.
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" data-close-approve-modal class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">إلغاء</button>
                <form id="approveCreditNoteForm" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">تأكيد الاعتماد</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('approveCreditNoteModal');
        const form = document.getElementById('approveCreditNoteForm');
        const text = document.getElementById('approveCreditNoteText');

        if (!modal || !form || !text) return;

        document.querySelectorAll('.js-open-approve-modal').forEach((button) => {
            button.addEventListener('click', () => {
                const action = button.getAttribute('data-approve-url');
                const noteNumber = button.getAttribute('data-note-number');
                form.setAttribute('action', action);
                text.textContent = `هل أنت متأكد من اعتماد الإشعار ${noteNumber}؟ سيتم توليد قيد محاسبي وتحديث رصيد العميل فوراً.`;
                modal.classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-close-approve-modal]').forEach((button) => {
            button.addEventListener('click', () => modal.classList.add('hidden'));
        });
    })();
</script>
@endpush

