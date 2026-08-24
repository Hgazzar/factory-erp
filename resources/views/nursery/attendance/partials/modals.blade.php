@php
    use App\Models\Nursery\LeaveRecord;
@endphp

{{-- أيام الحضور --}}
@foreach(['children', 'staff'] as $scopeKey)
<div x-show="modal === 'weekdays-{{ $scopeKey }}'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="closeModal()">
    <div class="nursery-card w-full max-w-md p-5 space-y-4" @click.outside="closeModal()">
        <h3 class="text-lg font-bold text-teal-950">تعيين أيام الحضور — {{ $scopeKey === 'children' ? 'الأطفال' : 'طاقم العمل' }}</h3>
        <form method="post" action="{{ route('nursery.attendance.weekdays') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="scope" value="{{ $scopeKey }}">
            <input type="hidden" name="tab" value="{{ $scopeKey === 'children' ? 'children' : 'staff' }}">
            <div class="nursery-age-group-list max-h-56 overflow-y-auto">
                @foreach($weekdayOptions as $opt)
                    @php $selected = $scopeKey === 'children' ? $childrenWeekdays : $staffWeekdays; @endphp
                    <label class="nursery-age-row">
                        <input type="checkbox" name="weekdays[]" value="{{ $opt['value'] }}"
                               class="rounded border-teal-300 text-teal-600"
                               @checked(in_array((int) $opt['value'], $selected, true))>
                        <span>{{ $opt['label'] }}</span>
                    </label>
                @endforeach
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeModal()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- تسجيل إجازة جماعي --}}
@foreach(['children' => $childOptions, 'staff' => $staffOptions] as $scopeKey => $subjectOptions)
<div x-show="modal === 'leave-{{ $scopeKey }}'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
    <div class="nursery-card w-full max-w-lg p-5 space-y-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-teal-950">تسجيل إجازة</h3>
        <form method="post" action="{{ route('nursery.attendance.leaves.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="scope" value="{{ $scopeKey }}">
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">اسم الإجازة <span class="text-red-600">*</span></label>
                <input type="text" name="name" required class="w-full rounded-lg border border-teal-200 px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold text-teal-950 mb-1">تاريخ البداية <span class="text-red-600">*</span></label>
                    <input type="date" name="starts_on" required class="w-full rounded-lg border border-teal-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-teal-950 mb-1">تاريخ الانتهاء <span class="text-red-600">*</span></label>
                    <input type="date" name="ends_on" required class="w-full rounded-lg border border-teal-200 px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">{{ $scopeKey === 'children' ? 'الأطفال' : 'الموظفون' }} <span class="text-red-600">*</span></label>
                <div class="nursery-age-group-list max-h-40 overflow-y-auto">
                    @foreach($subjectOptions as $opt)
                        <label class="nursery-age-row">
                            <input type="checkbox" name="{{ $scopeKey === 'children' ? 'child_ids' : 'employee_ids' }}[]"
                                   value="{{ $opt['value'] }}" class="rounded border-teal-300 text-teal-600">
                            <span>{{ $opt['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeModal()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">إضافة</button>
            </div>
        </form>
    </div>
</div>
@endforeach

{{-- إجازة لفرد واحد --}}
<div x-show="modal === 'leave-children-single' || modal === 'leave-staff-single'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
    <div class="nursery-card w-full max-w-lg p-5 space-y-4">
        <h3 class="text-lg font-bold text-teal-950">إجازات</h3>
        <form method="post" action="{{ route('nursery.attendance.leaves.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="scope" :value="modal === 'leave-children-single' ? 'children' : 'staff'">
            <input type="hidden" name="child_id" x-bind:value="leaveChildId">
            <input type="hidden" name="employee_id" x-bind:value="leaveEmployeeId">
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">اسم الإجازة <span class="text-red-600">*</span></label>
                <input type="text" name="name" required class="w-full rounded-lg border border-teal-200 px-3 py-2">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-semibold mb-1">من</label><input type="date" name="starts_on" required class="w-full rounded-lg border border-teal-200 px-3 py-2"></div>
                <div><label class="block text-sm font-semibold mb-1">إلى</label><input type="date" name="ends_on" required class="w-full rounded-lg border border-teal-200 px-3 py-2"></div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeModal()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ</button>
            </div>
        </form>
        <p class="text-xs text-teal-700/70">لحذف إجازة مسجّلة، استخدم قائمة الإجازات في التقرير أو تواصل مع المسؤول.</p>
    </div>
</div>

{{-- تقرير --}}
@foreach(['children', 'staff'] as $scopeKey)
<div x-show="modal === 'report-{{ $scopeKey }}' || modal === 'report-{{ $scopeKey }}-single'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
    <div class="nursery-card w-full max-w-lg p-5 space-y-4">
        <h3 class="text-lg font-bold text-teal-950">إعداد تقرير</h3>
        <form method="get" action="{{ route('nursery.attendance.report') }}" target="_blank" class="space-y-3">
            <input type="hidden" name="scope" value="{{ $scopeKey }}">
            <input type="hidden" name="child_id" x-bind:value="reportChildId">
            <input type="hidden" name="employee_id" x-bind:value="reportEmployeeId">
            @if($scopeKey === 'children')
                <div x-show="modal === 'report-children'">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">حدد الأطفال</label>
                    <div class="nursery-age-group-list max-h-36 overflow-y-auto">
                        @foreach($childOptions as $opt)
                            <label class="nursery-age-row">
                                <input type="checkbox" name="child_ids[]" value="{{ $opt['value'] }}" class="rounded border-teal-300 text-teal-600">
                                <span>{{ $opt['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <div x-show="modal === 'report-staff'">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">حدد الموظفين</label>
                    <div class="nursery-age-group-list max-h-36 overflow-y-auto">
                        @foreach($staffOptions as $opt)
                            <label class="nursery-age-row">
                                <input type="checkbox" name="employee_ids[]" value="{{ $opt['value'] }}" class="rounded border-teal-300 text-teal-600">
                                <span>{{ $opt['label'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-semibold mb-1">من</label><input type="date" name="starts_on" required class="w-full rounded-lg border border-teal-200 px-3 py-2"></div>
                <div><label class="block text-sm font-semibold mb-1">إلى</label><input type="date" name="ends_on" required class="w-full rounded-lg border border-teal-200 px-3 py-2"></div>
            </div>
            <div class="nursery-card p-3 bg-teal-50/60 border-teal-100">
                <div class="flex items-start gap-3">
                    <label class="nursery-switch shrink-0 mt-0.5">
                        <input type="checkbox" name="include_absence_reason" value="1" class="nursery-switch-input">
                        <span class="nursery-switch-track" aria-hidden="true"></span>
                    </label>
                    <div>
                        <p class="text-sm font-bold text-teal-950">
                            إرفاق سبب الغياب
                            <x-info field="nursery.attendance_include_absence_reason" />
                        </p>
                        <p class="text-xs text-teal-800/75 mt-1 leading-relaxed">
                            عند التفعيل يظهر في التقرير عمود <strong>السبب</strong>:
                            اسم الإجازة للأيام المسجّلة كإجازة،
                            أو «غائب — لم يُسجَّل حضور» لباقي أيام الغياب.
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" @click="closeModal()" class="nursery-btn nursery-btn-soft">إلغاء</button>
                <button type="submit" class="nursery-btn nursery-btn-primary">إعداد التقرير</button>
            </div>
        </form>
    </div>
</div>
@endforeach
