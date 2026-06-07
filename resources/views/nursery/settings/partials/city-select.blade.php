@php
    $regionKey = (string) ($regionKey ?? '');
    $cityValue = (string) ($cityValue ?? '');
@endphp
@if($regionKey === '')
    <label class="block text-sm font-semibold text-orange-950 mb-1">المدينة <x-info field="nursery.settings_city" /></label>
    <p class="text-sm text-orange-700/80 py-2">اختر المنطقة أولاً لعرض المدن.</p>
    <input type="hidden" name="city" value="">
@else
    <label class="block text-sm font-semibold text-orange-950 mb-1">المدينة <x-info field="nursery.settings_city" /></label>
    <x-custom-select name="city"
        :options="\App\Support\SaudiRegions::citySelectOptions($regionKey)"
        :value="$cityValue"
        placeholder="ابحث عن المدينة"
        empty-label="— اختر المدينة —"
        :searchable="true" />
    @error('city')
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
@endif
