@php
    $crmFollowUpTypeOptions = $crmFollowUpTypeOptions ?? \App\Models\CrmActivity::followUpTypesForModal();
    $crmFollowUpSelectOptions = collect($crmFollowUpTypeOptions)->map(fn ($label, $value) => ['value' => (string) $value, 'label' => $label])->values()->all();
@endphp

{{-- موعد سريع --}}
<div class="modal fade" id="crmQuickAppointmentModal" tabindex="-1" aria-labelledby="crmQuickAppointmentModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg">
            <div class="modal-header border-b border-gray-200">
                <h5 class="modal-title text-base font-semibold" id="crmQuickAppointmentModalLabel">إضافة موعد</h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="crmQuickAppointmentForm" method="POST" action="#">
                @csrf
                <div class="modal-body space-y-3">
                    <p class="text-sm text-gray-600 mb-0" id="crmQuickAppointmentCustomerLine"></p>
                    <div>
                        <label for="crm_appt_scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">موعد المتابعة <span class="text-red-500">*</span></label>
                        <input type="datetime-local" name="scheduled_at" id="crm_appt_scheduled_at" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="crm_appt_title" class="block text-sm font-medium text-gray-700 mb-1">عنوان (اختياري)</label>
                        <input type="text" name="title" id="crm_appt_title" maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-right" placeholder="مكالمة، زيارة، …">
                    </div>
                    <div>
                        <label for="crm_appt_result" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">النتيجة (اختياري) <x-info field="crm.appointment_activity_result" /></span></label>
                        <input type="text" name="result" id="crm_appt_result" maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-right" placeholder="مجدول، تأكيد، …">
                    </div>
                </div>
                <div class="modal-footer border-t border-gray-200">
                    <button type="button" class="btn btn-light rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-lg" style="background: #4f46e5; border: none;">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- تسجيل متابعة (مكالمة وغيرها) --}}
<div class="modal fade" id="crmQuickCallModal" tabindex="-1" aria-labelledby="crmQuickCallModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-lg">
            <div class="modal-header border-b border-gray-200">
                <h5 class="modal-title text-base font-semibold" id="crmQuickCallModalLabel">تسجيل متابعة</h5>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="crmQuickCallForm" method="POST" action="#">
                @csrf
                <div class="modal-body space-y-3">
                    <p class="text-sm text-gray-600 mb-0" id="crmQuickCallCustomerLine"></p>
                    <div>
                        <label for="crm_call_type" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">نوع المتابعة <span class="text-red-500">*</span> <x-info field="crm.activity_follow_up_type" /></span></label>
                        <x-searchable-select
                            name="type"
                            id="crm_call_type"
                            :options="$crmFollowUpSelectOptions"
                            value=""
                            :required="true"
                            empty-label="اختر النوع"
                            placeholder="ابحث…"
                            :searchable="false"
                            :in-modal="true"
                        />
                    </div>
                    <div>
                        <label for="crm_call_note" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">الملاحظات <x-info field="crm.activity_note" /></span></label>
                        <textarea name="note" id="crm_call_note" rows="3" maxlength="5000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-right" placeholder="تفاصيل المحادثة أو الملاحظات…"></textarea>
                    </div>
                    <div>
                        <label for="crm_call_result" class="block text-sm font-medium text-gray-700 mb-1"><span class="inline-flex items-center gap-1">النتيجة <x-info field="crm.activity_result" /></span></label>
                        <input type="text" name="result" id="crm_call_result" maxlength="255" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-right" placeholder="مثال: طلب عرض سعر، لم يرد، …">
                    </div>
                </div>
                <div class="modal-footer border-t border-gray-200">
                    <button type="button" class="btn btn-light rounded-lg" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-lg" style="background: #4f46e5; border: none;">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var apptModal = document.getElementById('crmQuickAppointmentModal');
    var apptForm = document.getElementById('crmQuickAppointmentForm');
    var apptLine = document.getElementById('crmQuickAppointmentCustomerLine');
    if (apptModal && apptForm && apptLine && window.bootstrap) {
        apptModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            var url = btn.getAttribute('data-appt-url');
            var label = btn.getAttribute('data-customer-label') || '';
            if (url) apptForm.setAttribute('action', url);
            apptLine.textContent = label ? ('العميل: ' + label) : '';
            apptForm.reset();
        });
    }
    var callModal = document.getElementById('crmQuickCallModal');
    var callForm = document.getElementById('crmQuickCallForm');
    var callLine = document.getElementById('crmQuickCallCustomerLine');
    if (callModal && callForm && callLine && window.bootstrap) {
        callModal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            if (!btn) return;
            var url = btn.getAttribute('data-call-url');
            var label = btn.getAttribute('data-customer-label') || '';
            if (url) callForm.setAttribute('action', url);
            callLine.textContent = label ? ('العميل: ' + label) : '';
            callForm.reset();
            window.dispatchEvent(new CustomEvent('erp-sync-searchable', { detail: { id: 'crm_call_type', value: '' } }));
        });
    }
});
</script>
@endpush
