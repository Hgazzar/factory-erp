@php
    $cityValue = (string) ($cityValue ?? '');
    $regionKey = (string) ($regionKey ?? '');
@endphp
@if($regionKey === '')
    <label class="block text-sm font-semibold text-teal-950 mb-1">
        المدينة
        <x-info field="nursery.staff_city" />
    </label>
    <p class="text-sm text-teal-700/80 py-2">اختر المنطقة أولاً.</p>
    <input type="hidden" name="city" value="">
@else
    <label class="block text-sm font-semibold text-teal-950 mb-1">
        المدينة
        <x-info field="nursery.staff_city" />
    </label>
    <x-custom-select name="city"
        :options="\App\Support\SaudiRegions::citySelectOptions($regionKey)"
        :value="$cityValue"
        placeholder="ابحث عن المدينة"
        empty-label="— اختر المدينة —"
        :searchable="true" />
@endif
