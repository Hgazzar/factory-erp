@extends('layouts.crm')

@section('title', 'موعد جديد — CRM — '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('crm.dashboard') }}" class="text-gray-500 hover:text-indigo-600">إدارة العملاء</a>
    <span>›</span>
    <a href="{{ route('crm.appointments.index') }}" class="text-gray-500 hover:text-indigo-600">المواعيد</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold truncate">موعد جديد</span>
@endsection

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-4xl font-bold text-gray-900 inline-flex items-center gap-2">
            موعد جديد
            <x-info field="crm.appointments_create_intro" />
        </h1>
    </div>

    <form method="POST" action="{{ route('crm.appointments.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-10">
            <div class="space-y-6 lg:col-span-7">
                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900">المعلومات الأساسية</h2>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label for="appt-number-preview" class="mb-1 block text-sm font-semibold text-slate-700">رقم الموعد</label>
                            <input id="appt-number-preview" type="text" value="{{ $nextAppointmentNumber ?? 'APP-001' }}" readonly class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 bg-gray-50 py-2.5 px-3 text-sm text-gray-700">
                        </div>
                        <div>
                            <label for="appt-type" class="mb-1 block text-sm font-semibold text-slate-700">النوع *</label>
                            <x-searchable-select
                                name="type"
                                id="appt-type"
                                :options="$typeOptions ?? []"
                                :value="old('type', 'meeting')"
                                :empty-option="false"
                                empty-label="اختر النوع"
                                placeholder="اختر النوع"
                                :searchable="false"
                            />
                            @error('type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="appt-assignee" class="mb-1 block text-sm font-semibold text-slate-700">مسؤول عنه</label>
                            <x-searchable-select
                                name="assigned_to"
                                id="appt-assignee"
                                :options="$assigneeOptions ?? []"
                                :value="old('assigned_to', '')"
                                :empty-option="false"
                                empty-label="اختر المسؤول"
                                placeholder="اختر المسؤول"
                                :searchable="false"
                            />
                            @error('assigned_to')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="appt-status" class="mb-1 block text-sm font-semibold text-slate-700">الحالة *</label>
                            <x-searchable-select
                                name="status"
                                id="appt-status"
                                :options="$statusOptions ?? []"
                                :value="old('status', 'planned')"
                                :empty-option="false"
                                empty-label="اختر الحالة"
                                placeholder="اختر الحالة"
                                :searchable="false"
                            />
                            @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="appt-title" class="mb-1 block text-sm font-semibold text-slate-700">الوصف *</label>
                            <textarea id="appt-title" name="title" rows="3" class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400" placeholder="ادخل وصف الموعد...">{{ old('title') }}</textarea>
                            @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900">العميل</h2>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <label for="appt-direct-attendance" class="mb-0 text-sm font-semibold text-slate-700">حضور مباشر</label>
                                <div class="form-check form-switch mb-0">
                                    <input id="appt-direct-attendance" type="checkbox" class="form-check-input" name="is_direct_attendance" value="1" @checked(old('is_direct_attendance') == '1')>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="appt-customer" class="mb-1 block text-sm font-semibold text-slate-700">العملاء *</label>
                            <x-searchable-select
                                name="customer_id"
                                id="appt-customer"
                                :options="$customerOptions ?? []"
                                :value="old('customer_id', '')"
                                :empty-option="false"
                                empty-label="اختر العميل"
                                placeholder="اختر العميل"
                            />
                            @error('customer_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900">التاريخ والوقت</h2>
                    <div class="mb-4 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <label for="appt-all-day" class="mb-0 text-sm font-semibold text-slate-700">طوال اليوم</label>
                            <div class="form-check form-switch mb-0">
                                <input id="appt-all-day" type="checkbox" class="form-check-input" name="is_all_day" value="1" @checked(old('is_all_day') == '1')>
                            </div>
                        </div>
                        <p class="mt-1 mb-0 text-xs text-slate-500">هذا الموعد يمتد طوال اليوم</p>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="appt-start-date" class="mb-1 block text-sm font-semibold text-slate-700">تاريخ البداية *</label>
                            <input id="appt-start-date" type="date" name="start_date_proxy" value="{{ old('start_date_proxy') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900">
                        </div>
                        <div>
                            <label for="appt-start-time" class="mb-1 block text-sm font-semibold text-slate-700">وقت البداية *</label>
                            <input id="appt-start-time" type="time" name="start_time_proxy" value="{{ old('start_time_proxy') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900">
                        </div>
                        <div>
                            <label for="appt-end-date" class="mb-1 block text-sm font-semibold text-slate-700">تاريخ النهاية *</label>
                            <input id="appt-end-date" type="date" name="end_date_proxy" value="{{ old('end_date_proxy') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900">
                        </div>
                        <div>
                            <label for="appt-end-time" class="mb-1 block text-sm font-semibold text-slate-700">وقت النهاية *</label>
                            <input id="appt-end-time" type="time" name="end_time_proxy" value="{{ old('end_time_proxy') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900">
                        </div>
                    </div>
                    <input type="hidden" id="appt-start" name="start_at" value="{{ old('start_at') }}">
                    <input type="hidden" id="appt-end" name="end_at" value="{{ old('end_at') }}">
                    @error('start_at')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    @error('end_at')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-2xl font-semibold text-gray-900">الموقع</h2>
                    <div class="space-y-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <label for="appt-online-meeting" class="mb-0 text-sm font-semibold text-slate-700">اجتماع افتراضي</label>
                                <div class="form-check form-switch mb-0">
                                    <input id="appt-online-meeting" type="checkbox" class="form-check-input" name="is_online_meeting" value="1" @checked(old('is_online_meeting') == '1')>
                                </div>
                            </div>
                            <p class="mt-1 mb-0 text-xs text-slate-500">هذا اجتماع عبر الإنترنت</p>
                        </div>
                        <div>
                        <label for="appt-location" class="mb-1 block text-sm font-semibold text-slate-700">الموقع</label>
                        <input id="appt-location" type="text" name="location" value="{{ old('location') }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400" placeholder="ادخل الموقع...">
                        @error('location')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-3">
                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">الإعدادات</h2>
                    <div class="space-y-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <label for="appt-send-reminder" class="mb-0 text-sm font-semibold text-slate-700">إرسال تذكير</label>
                                <div class="form-check form-switch mb-0">
                                    <input id="appt-send-reminder" type="checkbox" class="form-check-input" name="send_reminder" value="1" @checked(old('send_reminder', '1') == '1')>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="appt-reminder-minutes" class="mb-1 block text-sm font-semibold text-slate-700">تذكير قبل</label>
                            <x-searchable-select
                                name="reminder_minutes"
                                id="appt-reminder-minutes"
                                :options="[
                                    ['value' => '5', 'label' => '5 دقائق'],
                                    ['value' => '10', 'label' => '10 دقائق'],
                                    ['value' => '15', 'label' => '15 دقيقة'],
                                    ['value' => '30', 'label' => '30 دقيقة'],
                                    ['value' => '60', 'label' => '60 دقيقة'],
                                ]"
                                :value="old('reminder_minutes', '30')"
                                :empty-option="false"
                                empty-label="اختر المدة"
                                placeholder="اختر المدة"
                                :searchable="false"
                            />
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <label for="appt-reminder-owner-email" class="mb-0 text-sm font-semibold text-slate-700">ربط التذكير على إيميل صاحب هذا الموعد</label>
                                <div class="form-check form-switch mb-0">
                                    <input id="appt-reminder-owner-email" type="checkbox" class="form-check-input" name="reminder_owner_email" value="1" @checked(old('reminder_owner_email') == '1')>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900">ملاحظات</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="appt-notes" class="mb-1 block text-sm font-semibold text-slate-700">ملاحظات داخلية</label>
                            <textarea id="appt-notes" name="notes" rows="4" class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400" placeholder="ملاحظات للموظفين فقط...">{{ old('notes') }}</textarea>
                            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="appt-customer-notes" class="mb-1 block text-sm font-semibold text-slate-700">ملاحظات العميل</label>
                            <textarea id="appt-customer-notes" name="customer_notes" rows="3" class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-cyan-400 focus:border-cyan-400" placeholder="ملاحظات مرئية للعميل...">{{ old('customer_notes') }}</textarea>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <label for="appt-share-notes" class="mb-0 text-sm font-semibold text-slate-700">سيتم مشاركة هذه الملاحظات مع العميل</label>
                                <div class="form-check form-switch mb-0">
                                    <input id="appt-share-notes" type="checkbox" class="form-check-input" name="share_notes_with_customer" value="1" @checked(old('share_notes_with_customer') == '1')>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-gray-50/95 py-4 backdrop-blur supports-[backdrop-filter]:bg-gray-50/80">
            <div class="flex flex-wrap items-center gap-3 justify-start">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition border-0 shadow-sm min-h-[2.75rem] inline-flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8.5 1.5A1.5 1.5 0 0 0 7 3v2H5a.5.5 0 0 0 0 1h2v2a.5.5 0 0 0 1 0V6h2a.5.5 0 0 0 0-1H8V3a.5.5 0 0 1 .5-.5H11A1.5 1.5 0 0 1 12.5 4v8a1.5 1.5 0 0 1-1.5 1.5h-6A1.5 1.5 0 0 1 3.5 12V4A1.5 1.5 0 0 1 5 2.5h.5a.5.5 0 0 0 0-1z"/></svg>
                    إنشاء
                </button>
                <a href="{{ route('crm.appointments.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm font-semibold hover:bg-gray-50 transition no-underline min-h-[2.75rem] inline-flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L3.707 7.5H13.5a.5.5 0 0 1 0 1H3.707l2.147 2.146a.5.5 0 0 1-.708.708z"/></svg>
                    إلغاء
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var startDateEl = document.getElementById('appt-start-date');
    var startTimeEl = document.getElementById('appt-start-time');
    var endDateEl = document.getElementById('appt-end-date');
    var endTimeEl = document.getElementById('appt-end-time');
    var startHidden = document.getElementById('appt-start');
    var endHidden = document.getElementById('appt-end');

    function syncDateTimes() {
        if (startDateEl.value && startTimeEl.value) {
            startHidden.value = startDateEl.value + ' ' + startTimeEl.value + ':00';
        } else {
            startHidden.value = '';
        }

        if (endDateEl.value && endTimeEl.value) {
            endHidden.value = endDateEl.value + ' ' + endTimeEl.value + ':00';
        } else {
            endHidden.value = '';
        }
    }

    [startDateEl, startTimeEl, endDateEl, endTimeEl].forEach(function (el) {
        if (el) {
            el.addEventListener('change', syncDateTimes);
            el.addEventListener('input', syncDateTimes);
        }
    });

    syncDateTimes();
});
</script>
@endpush

