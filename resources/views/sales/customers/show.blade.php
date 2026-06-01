@extends('layouts.app')

@section('title', 'بيانات العميل - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span>›</span>
    <a href="{{ route('sales.dashboard') }}" class="text-gray-500 hover:text-indigo-600">المبيعات</a>
    <span>›</span>
    <a href="{{ route('sales.customers.index') }}" class="text-gray-500 hover:text-indigo-600">العملاء</a>
    <span>›</span>
    <span class="text-indigo-900 font-semibold">بيانات العميل</span>
@endsection

@section('content')
@php
    $currentMembership = $customer->currentMembership;
    $currentMembershipPlan = $currentMembership?->loyaltyProgram;
    $membershipColor = $currentMembershipPlan?->color ?: '#2563EB';
@endphp
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $customer->display_name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $customer->code }}</p>
            @if($customer->crmSegments->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <span class="text-xs text-gray-500">الشرائح:</span>
                    @foreach($customer->crmSegments as $segment)
                        <a href="{{ route('crm.segments.index', ['q' => $segment->name]) }}" class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 no-underline hover:bg-gray-50 hover:text-blue-700">
                            <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $segment->color ?? '#2563EB' }}"></span>
                            {{ $segment->name }}
                        </a>
                    @endforeach
                </div>
            @endif
            @if($currentMembership && $currentMembershipPlan)
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="text-xs text-gray-500">العضوية الحالية:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold text-white" style="background-color: {{ $membershipColor }}">
                        {{ $currentMembershipPlan->name }}
                    </span>
                </div>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.customers.edit', $customer) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">تعديل</a>
            <a href="{{ route('sales.customers.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gray-100 text-gray-800 text-sm font-medium hover:bg-gray-200">رجوع</a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
            <h2 class="text-base font-semibold text-gray-900"><span class="inline-flex items-center gap-1">الخطط والاشتراكات <x-info field="sales.customer_loyalty_card" /></span></h2>
            @if(($loyaltyProgramOptionsForEnroll ?? []) !== [])
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition shadow-sm border-0" data-bs-toggle="modal" data-bs-target="#loyaltyEnrollModal">
                    اشتراك جديد
                </button>
            @endif
        </div>
        @if($currentMembership && $currentMembershipPlan)
            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <span class="inline-flex items-center gap-2 text-gray-800 font-semibold">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full" style="background-color: {{ $membershipColor }}"></span>
                        خطة العضوية النشطة: {{ $currentMembershipPlan->name }}
                    </span>
                    <span class="text-gray-600">تاريخ البدء: {{ optional($currentMembership->start_date)->format('Y-m-d') ?? '—' }}</span>
                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium border {{ $currentMembership->auto_renew ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-gray-100 text-gray-700 border-gray-300' }}">
                        {{ $currentMembership->auto_renew ? 'تجديد تلقائي: نعم' : 'تجديد تلقائي: لا' }}
                    </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition"
                        data-bs-toggle="modal"
                        data-bs-target="#loyaltyEnrollModal"
                        title="تغيير الخطة"
                        aria-label="تغيير الخطة"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M15.502 1.94a.5.5 0 0 1 0 .706l-1.793 1.793-2.147-2.147L13.355.5a.5.5 0 0 1 .707 0l1.44 1.44zM10.854 3.646 2 12.5V15h2.5l8.854-8.854z"/></svg>
                    </button>
                </div>
            </div>
        @endif
        @if($customer->crmLoyaltyAccounts->isEmpty())
            <div class="text-sm text-gray-500 py-6 text-center rounded-lg bg-gray-50 border border-gray-100">
                لا يوجد اشتراك حالي.
                @if(($loyaltyProgramOptionsForEnroll ?? []) === [])
                    <span class="block mt-1 text-xs text-gray-400">جميع البرامج النشطة مُفعّلة أو لا توجد برامج نشطة.</span>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[32rem] text-sm border-collapse text-right">
                    <thead class="text-gray-600 border-b border-gray-200">
                        <tr>
                            <th class="py-2 px-2 font-medium">البرنامج</th>
                            <th class="py-2 px-2 font-medium">الرصيد</th>
                            <th class="py-2 px-2 font-medium">القيمة التقديرية</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($customer->crmLoyaltyAccounts as $acc)
                            @php
                                $prog = $acc->loyaltyProgram;
                                $mv = $prog ? ((float) $acc->current_balance * (float) $prog->redemption_rate) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50/80">
                                <td class="py-2 px-2 text-gray-900">
                                    @if($prog)
                                        {{ $prog->name }}
                                        <span class="block text-xs text-gray-500 font-mono">{{ $prog->code }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 px-2">
                                    <span class="inline-flex items-center gap-1.5 tabular-nums font-bold text-gray-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="text-amber-500" viewBox="0 0 16 16" aria-hidden="true"><path d="m8 .6 1.8 3.66 4.04.59-2.92 2.84.69 4.01L8 9.79l-3.61 1.91.69-4.01L2.16 4.85l4.04-.59z"/></svg>
                                        {{ number_format((float) $acc->current_balance, 2) }}
                                    </span>
                                </td>
                                <td class="py-2 px-2 tabular-nums text-gray-700">{{ number_format((float) $mv, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="modal fade" id="loyaltyEnrollModal" tabindex="-1" aria-labelledby="loyaltyEnrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg border border-gray-200 shadow-lg">
                <div class="modal-header border-gray-100">
                    <h5 class="modal-title text-base font-semibold" id="loyaltyEnrollModalLabel">إنشاء/تحديث اشتراك العميل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form method="POST" action="{{ route('sales.customers.loyalty-enroll', $customer) }}" class="modal-body pt-1" id="loyaltyEnrollForm" data-current-plan-id="{{ (string) ($currentMembership?->loyalty_program_id ?? '') }}" data-current-plan-name="{{ $currentMembershipPlan?->name ?? '' }}">
                    @csrf
                    <label for="enroll-program" class="block text-sm font-medium text-gray-700 mb-1">اختر الخطة</label>
                    <x-searchable-select
                        name="loyalty_program_id"
                        id="enroll-program"
                        :options="$loyaltyProgramOptionsForEnroll ?? []"
                        :value="old('loyalty_program_id', (string) ($currentMembership?->loyalty_program_id ?? ''))"
                        empty-label="—"
                        placeholder="ابحث بالخطة (رمز أو اسم)..."
                        :in-modal="true"
                    />
                    @error('loyalty_program_id')
                        <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-3">
                        <label for="enroll-start-date" class="block text-sm font-medium text-gray-700 mb-1">تاريخ البدء</label>
                        <input type="date" id="enroll-start-date" name="start_date" value="{{ old('start_date', optional($currentMembership?->start_date)->format('Y-m-d')) }}" class="block w-full min-h-[2.75rem] rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">اتركه فارغاً للبدء فوراً.</p>
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <p class="mb-0 text-sm text-gray-700">تجديد العضوية تلقائياً عند انتهائها</p>
                            <div class="form-check form-switch form-switch-lg ps-0 mb-0">
                                <input type="hidden" name="auto_renew" value="0">
                                <input class="form-check-input m-0" type="checkbox" role="switch" id="enroll-auto-renew" name="auto_renew" value="1" @checked(old('auto_renew', ($currentMembership?->auto_renew ? '1' : '0')) === '1')>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="enroll-notes" class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                        <textarea id="enroll-notes" name="notes" rows="3" class="block w-full rounded-lg border border-gray-300 py-2.5 px-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('notes', $currentMembership?->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <button type="button" class="inline-flex items-center justify-center min-h-[2.75rem] px-4 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="inline-flex items-center justify-center min-h-[2.75rem] px-5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('crm.partials.customer-profile-body', ['customer' => $customer])
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('loyaltyEnrollModal');
    var enrollForm = document.getElementById('loyaltyEnrollForm');
    var planInput = document.getElementById('enroll-program');

    if (enrollForm && planInput) {
        enrollForm.addEventListener('submit', function (e) {
            var currentPlanId = (enrollForm.getAttribute('data-current-plan-id') || '').trim();
            var currentPlanName = (enrollForm.getAttribute('data-current-plan-name') || '').trim();
            var selectedPlanId = (planInput.value || '').trim();

            if (currentPlanId !== '' && selectedPlanId !== '' && selectedPlanId !== currentPlanId) {
                var confirmationText = 'تنبيه: هذا العميل مشترك بالفعل في "' + (currentPlanName || 'الخطة الحالية') + '". هل أنت متأكد من رغبتك في استبدالها بالخطة الجديدة؟';
                e.preventDefault();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'تأكيد تغيير الخطة',
                        text: confirmationText,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، استبدال الخطة',
                        cancelButtonText: 'إلغاء',
                        reverseButtons: true
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            enrollForm.submit();
                        }
                    });
                } else if (window.confirm(confirmationText)) {
                    enrollForm.submit();
                }
            }
        });
    }

    @if($errors->has('loyalty_program_id') || $errors->has('start_date') || $errors->has('auto_renew') || $errors->has('notes'))
    if (modalEl && window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    @endif
});
</script>
@endpush
