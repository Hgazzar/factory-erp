@php
    $isEdit = $shift !== null;
    $startVal = old('start_time', $shift ? optional($shift->start_time)->format('H:i') : '08:00');
    $endVal = old('end_time', $shift ? optional($shift->end_time)->format('H:i') : '17:00');
@endphp

<form method="POST" action="{{ $action }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-6">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-800">الرمز <span class="text-red-600">*</span> <x-info field="hr.shift_code" /></label>
            <input type="text" name="code" value="{{ old('code', $shift?->code) }}" required maxlength="30" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 font-mono text-sm @error('code') border-red-500 @enderror">
            @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم بالعربية <span class="text-red-600">*</span> <x-info field="hr.shift_name_ar" /></label>
            <input type="text" name="name_ar" value="{{ old('name_ar', $shift?->name_ar) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm @error('name_ar') border-red-500 @enderror">
            @error('name_ar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-800">الاسم بالإنجليزية <x-info field="hr.shift_name_en" /></label>
            <input type="text" name="name_en" value="{{ old('name_en', $shift?->name_en) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-800">دقائق السماح <span class="text-red-600">*</span> <x-info field="hr.shift_grace_minutes" /></label>
            <input type="number" name="grace_minutes" min="0" max="180" value="{{ old('grace_minutes', $shift?->grace_minutes ?? 0) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm @error('grace_minutes') border-red-500 @enderror">
            @error('grace_minutes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-800">وقت البداية <span class="text-red-600">*</span> <x-info field="hr.shift_start_time" /></label>
            <input type="time" name="start_time" value="{{ $startVal }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm @error('start_time') border-red-500 @enderror">
            @error('start_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-800">وقت النهاية <span class="text-red-600">*</span> <x-info field="hr.shift_end_time" /></label>
            <input type="time" name="end_time" value="{{ $endVal }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm @error('end_time') border-red-500 @enderror">
            @error('end_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex flex-wrap gap-6">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800">
            <input type="checkbox" name="is_night" value="1" @checked(old('is_night', $shift?->is_night ?? false)) class="rounded border-gray-300">
            <span>وردية ليلية (تعبر منتصف الليل) <x-info field="hr.shift_is_night" /></span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shift?->is_active ?? true)) class="rounded border-gray-300">
            <span>نشط <x-info field="hr.shift_status" /></span>
        </label>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
        <a href="{{ route('hr.shifts.index') }}" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm hover:bg-gray-50">إلغاء</a>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الوردية' }}</button>
    </div>
</form>
