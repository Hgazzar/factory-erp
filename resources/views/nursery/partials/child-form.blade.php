@php
    /** @var \App\Models\Nursery\Child|null $child */
    $child = $child ?? null;
    $guardian = $child?->guardian;
    $formAction = $formAction ?? '';
    $formMethod = $formMethod ?? 'POST';
    $submitLabel = $submitLabel ?? 'حفظ';
    $regionValue = old('guardian_region', $guardian?->region ?? '');
    $cityValue = old('guardian_city', $guardian?->city ?? '');
@endphp

<form method="post" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-5" id="nurseryChildForm">
    @csrf
    @if(strtoupper($formMethod) !== 'POST')
        @method($formMethod)
    @endif

    <div class="grid gap-5 lg:grid-cols-2 items-start">
        <section class="nursery-card p-5 space-y-4 lg:col-span-2">
            <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">المعلومات الأساسية</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <x-nursery-avatar-upload
                        :name="old('name', $child?->name)"
                        :src="$child?->firstImageUrl()"
                        info-field="nursery.child_avatar"
                    />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        الاسم <span class="text-red-600">*</span>
                        <x-info field="nursery.child_name" />
                    </label>
                    <input type="text" name="name" value="{{ old('name', $child?->name) }}" required
                           class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        الجنس
                        <x-info field="nursery.child_gender" />
                    </label>
                    <x-custom-select name="gender" :options="$genderOptions" :value="old('gender', $child?->gender)"
                        placeholder="اختر الجنس" empty-label="—" :searchable="false" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        تاريخ الميلاد (ميلادي)
                        <x-info field="nursery.child_birth_date" />
                    </label>
                    <input type="date" name="date_of_birth"
                           value="{{ old('date_of_birth', $child?->date_of_birth?->format('Y-m-d')) }}"
                           max="{{ now()->toDateString() }}"
                           class="w-full rounded-lg border border-orange-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        الفصل
                        <x-info field="nursery.child_classroom" />
                    </label>
                    <x-custom-select name="classroom_id"
                        :options="$classrooms->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all()"
                        :value="old('classroom_id', (string) ($child?->activeEnrollment?->classroom_id ?? ''))"
                        placeholder="اختر الفصل" empty-label="— بدون فصل —" :searchable="true" />
                </div>
                @if($child !== null)
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">
                            حالة الحساب
                            <x-info field="nursery.child_account_status" />
                        </label>
                        <x-custom-select name="status"
                            :options="[['value' => 'active', 'label' => 'نشط'], ['value' => 'inactive', 'label' => 'مؤرشف']]"
                            :value="old('status', $child->status)"
                            :searchable="false" />
                    </div>
                @endif
            </div>
        </section>

        <section class="nursery-card p-5 space-y-4">
            <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">المعلومات الصحية</h2>
            <div>
                <label class="block text-sm font-semibold text-orange-950 mb-1">
                    الحساسية
                    <x-info field="nursery.allergies" />
                </label>
                <textarea name="allergies" rows="2" class="w-full rounded-lg border border-orange-200 px-3 py-2">{{ old('allergies', $child?->allergies) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-orange-950 mb-1">
                    الأمراض
                    <x-info field="nursery.child_diseases" />
                </label>
                <textarea name="diseases" rows="2" class="w-full rounded-lg border border-orange-200 px-3 py-2">{{ old('diseases', $child?->diseases) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-orange-950 mb-1">
                    ملاحظات صحية
                    <x-info field="nursery.child_health_notes" />
                </label>
                <textarea name="health_notes" rows="2" class="w-full rounded-lg border border-orange-200 px-3 py-2">{{ old('health_notes', $child?->health_notes) }}</textarea>
            </div>
            @include('nursery.partials.child-medications', ['child' => $child ?? null])
        </section>

        <section class="nursery-card p-5 space-y-4">
            <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">معلومات ولي الأمر</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        الاسم <span class="text-red-600">*</span>
                        <x-info field="nursery.guardian_name" />
                    </label>
                    <input type="text" name="guardian_name" value="{{ old('guardian_name', $guardian?->name) }}" required
                           class="w-full rounded-lg border border-orange-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        العلاقة
                        <x-info field="nursery.guardian_relationship" />
                    </label>
                    <x-custom-select name="guardian_relationship" :options="$relationshipOptions"
                        :value="old('guardian_relationship', $child?->guardian_relationship)"
                        placeholder="العلاقة" empty-label="—" :searchable="false" />
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        رقم الجوال <span class="text-red-600">*</span>
                        <x-info field="nursery.guardian_phone" />
                    </label>
                    <input type="text" name="guardian_phone" value="{{ old('guardian_phone', $guardian?->phone) }}" required
                           class="w-full rounded-lg border border-orange-200 px-3 py-2" dir="ltr">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        رقم بطاقة الهوية
                        <x-info field="nursery.guardian_national_id" />
                    </label>
                    <input type="text" name="guardian_national_id" value="{{ old('guardian_national_id', $guardian?->national_id) }}"
                           class="w-full rounded-lg border border-orange-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        البريد الإلكتروني
                        <x-info field="nursery.guardian_email" />
                    </label>
                    <input type="email" name="guardian_email" value="{{ old('guardian_email', $guardian?->email) }}"
                           class="w-full rounded-lg border border-orange-200 px-3 py-2" dir="ltr">
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        العنوان
                        <x-info field="nursery.guardian_address" />
                    </label>
                    <input type="text" name="guardian_address" value="{{ old('guardian_address', $guardian?->address) }}"
                           class="w-full rounded-lg border border-orange-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        المنطقة
                        <x-info field="nursery.guardian_region" />
                    </label>
                    <x-custom-select name="guardian_region" id="guardian_region_select"
                        :options="\App\Support\SaudiRegions::regionSelectOptions()"
                        :value="$regionValue"
                        placeholder="اختر المنطقة"
                        empty-label="— اختر المنطقة —"
                        :searchable="true" />
                </div>
                <div id="nursery-city-field-wrap">
                    @include('nursery.partials.city-select', ['regionKey' => $regionValue, 'cityValue' => $cityValue])
                </div>
            </div>
        </section>

        <section class="nursery-card p-5 space-y-4 lg:col-span-2">
            <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">
                مستندات الطفل
                <x-info field="nursery.child_attachments" />
            </h2>
            @if($child !== null && $child->relationLoaded('attachments'))
                <x-attachment-handler
                    theme="tailwind"
                    hint-field="nursery.child_attachments"
                    title="المرفقات الحالية"
                    :existing="$child->documentAttachments()"
                    :uploadable="false"
                    :allow-delete="true"
                    help-text="يمكنك حذف مرفق قديم. لإضافة ملفات جديدة استخدم حقل الرفع أدناه."
                />
            @endif
            <div>
                <label class="block text-sm font-semibold text-orange-950 mb-1">
                    رفع ملفات (يمكن اختيار أكثر من ملف)
                    <x-info field="nursery.child_attachments_upload" />
                </label>
                <input type="file" name="attachments[]" multiple
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                       class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm bg-white">
            </div>
        </section>
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <button type="submit" class="nursery-btn nursery-btn-primary">{{ $submitLabel }}</button>
        @canFeature(\App\Support\PremiumFeatureKeys::NURSERY_PORTAL)
            <label class="inline-flex items-center gap-2 text-sm text-orange-900 cursor-pointer">
                <input type="checkbox" name="send_portal_invite" value="1" class="rounded border-orange-300 text-orange-600">
                <span>حفظ وإرسال دعوة البوابة <x-info field="nursery.portal_invite_on_save" /></span>
            </label>
        @endcanFeature
        <a href="{{ $child ? route('nursery.children.show', $child) : route('nursery.children.index') }}" class="nursery-btn nursery-btn-soft">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const partialUrl = @json(route('nursery.partials.city-select'));

    function loadCitySelect(region, city) {
        const wrap = document.getElementById('nursery-city-field-wrap');
        if (!wrap) return;
        const params = new URLSearchParams();
        if (region) params.set('region', region);
        if (city) params.set('city', city);
        fetch(partialUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        })
            .then(r => r.text())
            .then(html => { wrap.innerHTML = html; })
            .catch(() => {});
    }

    document.addEventListener('searchable-select-change', function (e) {
        const d = e.detail || {};
        if (d.name !== 'guardian_region') return;
        loadCitySelect(d.value || '', '');
    });

    document.addEventListener('custom-select-change', function (e) {
        const d = e.detail || {};
        if (d.name !== 'guardian_region') return;
        loadCitySelect(d.value || '', '');
    });
})();
</script>
@endpush
