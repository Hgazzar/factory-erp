{{-- أخطاء التحقق: شريط أحمر موحّد أعلى المحتوى مع قائمة الرسائل (مثل باقي شاشات ERP) --}}
@if (isset($errors) && $errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm flex items-start justify-between gap-3" role="alert">
        <div class="min-w-0 flex-1">
            <p class="font-semibold">يرجى مراجعة الحقول المطلوبة أو تصحيح القيم أدناه.</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-red-800">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="shrink-0 text-red-600 hover:text-red-900 leading-none text-xl" onclick="this.closest('[role=alert]').remove()" aria-label="إغلاق">&times;</button>
    </div>
@endif

{{-- رسائل الجلسة الموحّدة بعد إضافة/تعديل/حذف (تعمل بدون الاعتماد على Livewire فقط) --}}
@php
    $flashSuccess = session('success');
    if ($flashSuccess === null || $flashSuccess === '') {
        $flashSuccess = session('status');
    }
    if (($flashSuccess === null || $flashSuccess === '') && ! session()->has('error')) {
        $flashSuccess = session('message');
    }
@endphp

@if (session()->has('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 flex items-start justify-between gap-3" role="alert">
        <span class="min-w-0 flex-1">{{ session('error') }}</span>
        <button type="button" class="shrink-0 text-red-600 hover:text-red-900 leading-none text-xl" onclick="this.closest('[role=alert]').remove()" aria-label="إغلاق">&times;</button>
    </div>
@endif

@if (session()->has('warning'))
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 flex items-start justify-between gap-3" role="alert">
        <span class="min-w-0 flex-1">{{ session('warning') }}</span>
        <button type="button" class="shrink-0 text-amber-700 hover:text-amber-950 leading-none text-xl" onclick="this.closest('[role=alert]').remove()" aria-label="إغلاق">&times;</button>
    </div>
@endif

@if ($flashSuccess !== null && $flashSuccess !== '')
    <div class="mb-4 erp-alert-success-inline flex items-start justify-between gap-3" role="alert" data-auto-dismiss-success>
        <span class="min-w-0 flex-1">{{ $flashSuccess }}</span>
        <button type="button" class="shrink-0 text-emerald-800 hover:opacity-75 leading-none text-xl" onclick="this.closest('[role=alert]').remove()" aria-label="إغلاق">&times;</button>
    </div>
@endif

@if (session()->has('info'))
    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 flex items-start justify-between gap-3" role="alert">
        <span class="min-w-0 flex-1">{{ session('info') }}</span>
        <button type="button" class="shrink-0 text-blue-700 hover:text-blue-950 leading-none text-xl" onclick="this.closest('[role=alert]').remove()" aria-label="إغلاق">&times;</button>
    </div>
@endif
