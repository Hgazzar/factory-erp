@php
    /** @var \App\Models\Employee|null $employee */
    $employee = $employee ?? null;
    $formAction = $formAction ?? '';
    $formMethod = $formMethod ?? 'POST';
    $submitLabel = $submitLabel ?? 'حفظ';
    $regionValue = old('region', $employee?->region ?? '');
    $cityValue = old('city', $employee?->city ?? '');
    $selectedPerms = \App\Support\NurseryPermissionCatalog::normalize(old('permissions', $employee?->nursery_permissions ?? []));
@endphp

<form method="post" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-5" id="nurseryStaffForm">
    @csrf
    @if(strtoupper($formMethod) !== 'POST')
        @method($formMethod)
    @endif

    <div class="grid gap-5 xl:grid-cols-[1fr_minmax(280px,360px)]">
        <div class="space-y-5">
            <section class="nursery-card p-5 space-y-4">
                <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">بيانات الموظف</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-nursery-avatar-upload
                            :name="trim((string) old('first_name', $employee?->first_name ?? '').' '.(string) old('last_name', $employee?->last_name ?? ''))"
                            :src="$employee?->firstImageUrl()"
                            info-field="nursery.staff_avatar"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">الاسم الأول <span class="text-red-600">*</span> <x-info field="nursery.staff_first_name" /></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $employee?->first_name) }}" required class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">اسم العائلة <span class="text-red-600">*</span> <x-info field="nursery.staff_last_name" /></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $employee?->last_name) }}" required class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">رقم الهوية <x-info field="nursery.staff_id_number" /></label>
                        <input type="text" name="id_number" value="{{ old('id_number', $employee?->id_number) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">الجنس <x-info field="nursery.staff_gender" /></label>
                        <x-custom-select name="gender" :options="$genderOptions" :value="old('gender', $employee?->gender)" :searchable="false" empty-label="—" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">تاريخ الميلاد <x-info field="nursery.staff_birth_date" /></label>
                        <input type="date" name="birth_date" value="{{ old('birth_date', $employee?->birth_date?->format('Y-m-d')) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">الدور الوظيفي <x-info field="nursery.staff_job_role" /></label>
                        <x-custom-select name="nursery_job_role" :options="$jobRoleOptions" :value="old('nursery_job_role', $employee?->nursery_job_role)" :searchable="true" empty-label="— اختر الدور —" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">مناوبة الحضانة <x-info field="nursery.staff_nursery_shift" /></label>
                        <x-custom-select name="nursery_shift_id" :options="$shiftOptions ?? []"
                            :value="old('nursery_shift_id', $employee?->nursery_shift_id ? (string) $employee->nursery_shift_id : '')"
                            :searchable="count($shiftOptions ?? []) > 6"
                            empty-label="— بدون مناوبة —" />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">المؤهل <x-info field="nursery.staff_education" /></label>
                        <input type="text" name="nursery_education" value="{{ old('nursery_education', $employee?->nursery_education) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">التخصص <x-info field="nursery.staff_specialization" /></label>
                        <input type="text" name="nursery_specialization" value="{{ old('nursery_specialization', $employee?->nursery_specialization) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">رقم الجوال <span class="text-red-600">*</span> <x-info field="nursery.staff_mobile" /></label>
                        <input type="text" name="mobile" value="{{ old('mobile', $employee?->mobile ?? $employee?->phone) }}" required class="w-full rounded-lg border border-orange-200 px-3 py-2" dir="ltr">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">البريد الإلكتروني <span class="text-red-600">*</span> <x-info field="nursery.staff_email" /></label>
                        <input type="email" name="email" value="{{ old('email', $employee?->email) }}" required class="w-full rounded-lg border border-orange-200 px-3 py-2" dir="ltr">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-orange-950 mb-1">العنوان <x-info field="nursery.staff_address" /></label>
                        <input type="text" name="address" value="{{ old('address', $employee?->address) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">المنطقة <x-info field="nursery.staff_region" /></label>
                        <x-custom-select name="region" id="staff_region_select"
                            :options="\App\Support\SaudiRegions::regionSelectOptions()"
                            :value="$regionValue"
                            placeholder="اختر المنطقة"
                            empty-label="—"
                            :searchable="true" />
                    </div>
                    <div id="nursery-staff-city-wrap">
                        @include('nursery.partials.staff-city-select', ['regionKey' => $regionValue, 'cityValue' => $cityValue])
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">دور التشغيل <x-info field="nursery.employee_nursery_role" /></label>
                        <x-custom-select name="nursery_role" :options="$systemRoleOptions" :value="old('nursery_role', $employee?->nursery_role)" :searchable="false" empty-label="— بدون دور تشغيل —" />
                    </div>
                    @if($employee !== null)
                        <div>
                            <label class="block text-sm font-semibold text-orange-950 mb-1">الحالة <x-info field="nursery.staff_status" /></label>
                            <x-custom-select name="status"
                                :options="[['value' => 'active', 'label' => 'نشط'], ['value' => 'inactive', 'label' => 'مؤرشف']]"
                                :value="old('status', $employee->status)"
                                :searchable="false" />
                        </div>
                    @endif
                </div>
            </section>

            <section class="nursery-card p-5 space-y-4">
                <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">
                    مستندات الموظف
                    <x-info field="nursery.staff_attachments" />
                </h2>
                @if($employee !== null && $employee->relationLoaded('attachments'))
                    <x-attachment-handler theme="tailwind" hint-field="nursery.staff_attachments" title="المرفقات الحالية"
                        :existing="$employee->documentAttachments()" :uploadable="false" :allow-delete="true" />
                @endif
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">رفع ملفات <x-info field="nursery.staff_attachments_upload" /></label>
                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                           class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm bg-white">
                </div>
            </section>
        </div>

        @include('nursery.partials.staff-permissions-panel', [
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => $selectedPerms,
            'grantableKeys' => $grantableKeys,
            'canGrantAll' => $canGrantAll,
        ])
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" name="submit_action" value="save" class="nursery-btn nursery-btn-primary">{{ $submitLabel }}</button>
        @if($employee === null)
            <button type="submit" name="submit_action" value="save_and_invite" class="nursery-btn nursery-btn-soft">حفظ وإنشاء حساب دخول</button>
        @endif
        <a href="{{ route('nursery.staff.index') }}" class="nursery-btn nursery-btn-soft">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const partialUrl = @json(route('nursery.staff.partials.city-select'));
    function loadCity(region, city) {
        const wrap = document.getElementById('nursery-staff-city-wrap');
        if (!wrap) return;
        const params = new URLSearchParams();
        if (region) params.set('region', region);
        if (city) params.set('city', city);
        fetch(partialUrl + '?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text()).then(html => { wrap.innerHTML = html; });
    }
    const templates = @json($rolePermissionTemplates ?? []);
    const grantable = @json($grantableKeys ?? []);

    function applyRoleTemplate(role) {
        const keys = templates[role] || [];
        keys.forEach(function (key) {
            if (grantable.indexOf(key) === -1) return;
            const input = document.querySelector('#nurseryStaffForm input[name="permissions[]"][value="' + key + '"]');
            if (input && !input.disabled) {
                input.checked = true;
            }
        });
    }

    function onStaffSelectChange(e) {
        const detail = e.detail || {};
        if (detail.name === 'region') {
            loadCity(detail.value || '', '');
        }
        if (detail.name === 'nursery_role') {
            applyRoleTemplate(detail.value || '');
        }
    }

    document.addEventListener('searchable-select-change', onStaffSelectChange);
    document.addEventListener('custom-select-change', onStaffSelectChange);
})();
</script>
@endpush
