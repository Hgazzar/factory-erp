@php
    $editing = $errors->any() || old('_editing') === '1';
@endphp
<section class="nursery-card p-5 space-y-6" x-data="{ editing: @json($editing) }">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-orange-100 pb-3">
        <h2 class="text-lg font-bold text-orange-950">المعلومات الأساسية</h2>
        @if($canManage)
            <button type="button" x-show="!editing" @click="editing = true" class="nursery-btn nursery-btn-primary text-sm">تعديل</button>
        @endif
    </div>

    <div x-show="!editing" x-cloak class="space-y-6">
        <div>
            <h3 class="text-sm font-bold text-orange-900 mb-3">الهوية البصرية</h3>
            <div class="flex flex-wrap items-center gap-4 mb-2 p-3 rounded-lg bg-orange-50/50 border border-orange-100">
                <div class="w-14 h-14 rounded-xl border border-orange-200 bg-white flex items-center justify-center overflow-hidden p-1 shrink-0">
                    @if($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="" class="max-w-full max-h-full object-contain">
                    @else
                        <span class="text-xl" aria-hidden="true">🧸</span>
                    @endif
                </div>
                <div class="text-sm">
                    <p class="font-semibold text-orange-950">{{ $settings->portalDisplayName() }}</p>
                    @if($canManage)
                        <a href="{{ route('nursery.settings.index', ['tab' => 'branding']) }}" class="text-xs text-orange-600 font-bold hover:underline">رفع أو تغيير الشعار ←</a>
                    @endif
                </div>
            </div>
        </div>
        <div>
            <h3 class="text-sm font-bold text-orange-900 mb-3">بيانات الحضانة</h3>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                <div><dt class="text-orange-700/80 font-medium">اسم الحضانة <x-info field="nursery.settings_nursery_name" /></dt><dd class="font-semibold text-orange-950">{{ $settings->nursery_name }}</dd></div>
                <div><dt class="text-orange-700/80 font-medium">رقم التواصل <x-info field="nursery.settings_contact_phone" /></dt><dd class="font-semibold text-orange-950">{{ $settings->contact_phone ?: '—' }}</dd></div>
                <div><dt class="text-orange-700/80 font-medium">البريد الإلكتروني <x-info field="nursery.settings_contact_email" /></dt><dd class="font-semibold text-orange-950">{{ $settings->contact_email ?: '—' }}</dd></div>
                <div><dt class="text-orange-700/80 font-medium">العنوان <x-info field="nursery.settings_address" /></dt><dd class="font-semibold text-orange-950">{{ $settings->address ?: '—' }}</dd></div>
                <div><dt class="text-orange-700/80 font-medium">المنطقة <x-info field="nursery.settings_region" /></dt><dd class="font-semibold text-orange-950">{{ $regionLabel ?: '—' }}</dd></div>
                <div><dt class="text-orange-700/80 font-medium">المدينة <x-info field="nursery.settings_city" /></dt><dd class="font-semibold text-orange-950">{{ $settings->city ?: '—' }}</dd></div>
            </dl>
        </div>
        <div>
            <h3 class="text-sm font-bold text-orange-900 mb-3">بيانات مدير الحضانة</h3>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                <div><dt class="text-orange-700/80 font-medium">اسم المدير <x-info field="nursery.settings_manager_name" /></dt><dd class="font-semibold text-orange-950">{{ $settings->manager_name ?: '—' }}</dd></div>
                <div><dt class="text-orange-700/80 font-medium">رقم الجوال <x-info field="nursery.settings_manager_mobile" /></dt><dd class="font-semibold text-orange-950">{{ $settings->manager_mobile ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-orange-700/80 font-medium">البريد الإلكتروني <x-info field="nursery.settings_manager_email" /></dt><dd class="font-semibold text-orange-950">{{ $settings->manager_email ?: '—' }}</dd></div>
            </dl>
        </div>
        <p class="text-xs text-orange-700/60">آخر تحديث {{ $settings->updated_at?->locale('ar')->translatedFormat('j F Y — h:i a') }}</p>
    </div>

    @if($canManage)
        <form x-show="editing" x-cloak method="POST" action="{{ route('nursery.settings.account.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="_editing" value="1">

            <div>
                <h3 class="text-sm font-bold text-orange-900 mb-3">بيانات الحضانة</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-orange-950 mb-1">اسم الحضانة * <x-info field="nursery.settings_nursery_name" /></label>
                        <input type="text" name="nursery_name" required value="{{ old('nursery_name', $settings->nursery_name) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                        @error('nursery_name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">رقم التواصل <x-info field="nursery.settings_contact_phone" /></label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">البريد الإلكتروني <x-info field="nursery.settings_contact_email" /></label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-orange-950 mb-1">العنوان <x-info field="nursery.settings_address" /></label>
                        <input type="text" name="address" value="{{ old('address', $settings->address) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">المنطقة <x-info field="nursery.settings_region" /></label>
                        <x-custom-select name="region" id="settings_region_select"
                            :options="$regionOptions"
                            :value="old('region', $settings->region)"
                            placeholder="اختر المنطقة"
                            empty-label="— اختر المنطقة —"
                            :searchable="true" />
                    </div>
                    <div id="settings-city-field-wrap">
                        @include('nursery.settings.partials.city-select', [
                            'regionKey' => old('region', $settings->region ?? ''),
                            'cityValue' => old('city', $settings->city ?? ''),
                        ])
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-orange-900 mb-3">بيانات مدير الحضانة</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">اسم المدير <x-info field="nursery.settings_manager_name" /></label>
                        <input type="text" name="manager_name" value="{{ old('manager_name', $settings->manager_name) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-orange-950 mb-1">رقم الجوال <x-info field="nursery.settings_manager_mobile" /></label>
                        <input type="text" name="manager_mobile" value="{{ old('manager_mobile', $settings->manager_mobile) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-orange-950 mb-1">البريد الإلكتروني <x-info field="nursery.settings_manager_email" /></label>
                        <input type="email" name="manager_email" value="{{ old('manager_email', $settings->manager_email) }}" class="w-full rounded-lg border border-orange-200 px-3 py-2">
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="nursery-btn nursery-btn-primary">حفظ</button>
                <button type="button" @click="editing = false" class="nursery-btn nursery-btn-soft">إلغاء</button>
            </div>
        </form>
    @endif
</section>

@push('scripts')
<script>
(function () {
    const partialUrl = @json(route('nursery.settings.partials.city-select'));
    function loadCitySelect(region, city) {
        const wrap = document.getElementById('settings-city-field-wrap');
        if (!wrap) return;
        const params = new URLSearchParams();
        if (region) params.set('region', region);
        if (city) params.set('city', city);
        fetch(partialUrl + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        }).then(r => r.text()).then(html => { wrap.innerHTML = html; }).catch(() => {});
    }
    document.addEventListener('searchable-select-change', function (e) {
        if ((e.detail || {}).name !== 'region') return;
        loadCitySelect(e.detail.value || '', '');
    });
    document.addEventListener('custom-select-change', function (e) {
        if ((e.detail || {}).name !== 'region') return;
        loadCitySelect(e.detail.value || '', '');
    });
})();
</script>
@endpush
