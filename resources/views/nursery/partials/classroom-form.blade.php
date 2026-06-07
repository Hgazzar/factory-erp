@php
    /** @var \App\Models\Nursery\Classroom|null $classroom */
    $classroom = $classroom ?? null;
    $formAction = $formAction ?? '';
    $formMethod = $formMethod ?? 'POST';
    $submitLabel = $submitLabel ?? 'حفظ';
    $selectedAgeGroups = old('age_groups', $classroom?->age_groups ?? []);
    if (! is_array($selectedAgeGroups)) {
        $selectedAgeGroups = [];
    }
    $capacityValue = old('capacity', $classroom?->capacity ?? 10);
@endphp

<form method="post" action="{{ $formAction }}" class="space-y-5" id="nurseryClassroomForm">
    @csrf
    @if(strtoupper($formMethod) !== 'POST')
        @method($formMethod)
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="nursery-card p-5 space-y-4 lg:col-span-2">
            <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">بيانات الفصل</h2>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        اسم الفصل <span class="text-red-600">*</span>
                        <x-info field="nursery.classroom_name" />
                    </label>
                    <input type="text" name="name" value="{{ old('name', $classroom?->name) }}" required
                           class="w-full rounded-lg border border-orange-200 px-3 py-2"
                           placeholder="مثال: البراعم">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        سعة الفصل <span class="text-red-600">*</span>
                        <x-info field="nursery.classroom_capacity" />
                    </label>
                    <div class="flex items-stretch gap-0 rounded-lg border border-orange-200 overflow-hidden bg-white" dir="ltr">
                        <button type="button" class="nursery-capacity-step px-3 py-2 text-orange-700 bg-orange-50 hover:bg-orange-100 font-bold border-0"
                                data-step="-1" aria-label="إنقاص">−</button>
                        <div class="flex-1 flex items-center justify-center gap-2 px-2 min-w-0">
                            <input type="number" name="capacity" id="classroom_capacity_input" value="{{ $capacityValue }}"
                                   min="1" max="200" step="1" inputmode="numeric" required
                                   class="nursery-capacity-input w-full text-center border-0 focus:ring-0 py-2 text-orange-950 font-semibold">
                            <span class="text-sm text-orange-800/70 shrink-0" dir="rtl">طفل</span>
                        </div>
                        <button type="button" class="nursery-capacity-step px-3 py-2 text-orange-700 bg-orange-50 hover:bg-orange-100 font-bold border-0"
                                data-step="1" aria-label="زيادة">+</button>
                    </div>
                    @error('capacity')<p class="text-sm text-red-600 mt-1" dir="rtl">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        الفئة العمرية <span class="text-red-600">*</span>
                        <x-info field="nursery.classroom_age_groups" />
                    </label>
                    <div class="nursery-age-group-list max-h-[min(22rem,55vh)] overflow-y-auto">
                        <label class="nursery-age-row nursery-age-row--header">
                            <input type="checkbox" id="nursery_age_groups_select_all" class="rounded border-orange-300 text-orange-600 focus:ring-orange-500 shrink-0">
                            <span class="flex-1">تحديد الكل</span>
                        </label>
                        @foreach($ageGroupLabels as $key => $label)
                            <label class="nursery-age-row {{ $loop->iteration % 2 === 0 ? 'nursery-age-row--stripe' : '' }}">
                                <input type="checkbox" name="age_groups[]" value="{{ $key }}"
                                       class="nursery-age-group-cb rounded border-orange-300 text-orange-600 focus:ring-orange-500 shrink-0"
                                       @checked(in_array($key, $selectedAgeGroups, true))>
                                <span class="flex-1 leading-snug">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('age_groups')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('age_groups.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                @if($classroom !== null)
                    <div class="max-w-xs">
                        <label class="block text-sm font-semibold text-orange-950 mb-1">
                            حالة الفصل
                            <x-info field="nursery.classroom_status" />
                        </label>
                        <x-custom-select name="is_active"
                            :options="[['value' => 'active', 'label' => 'نشط'], ['value' => 'inactive', 'label' => 'مؤرشف']]"
                            :value="old('is_active', $classroom->is_active ? 'active' : 'inactive')"
                            :searchable="false" />
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" name="submit_action" value="save" class="nursery-btn nursery-btn-primary">{{ $submitLabel }}</button>
        @if($classroom === null)
            <button type="submit" name="submit_action" value="save_and_new" class="nursery-btn nursery-btn-soft border-orange-300">حفظ وإضافة جديد</button>
        @endif
        <a href="{{ route('nursery.classrooms.index') }}" class="nursery-btn nursery-btn-soft">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('nurseryClassroomForm');
    if (!form || form.dataset.capacityBound === '1') return;
    form.dataset.capacityBound = '1';

    const input = document.getElementById('classroom_capacity_input');
    form.querySelectorAll('.nursery-capacity-step').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (!input) return;
            const step = parseInt(btn.getAttribute('data-step') || '0', 10);
            const min = parseInt(input.getAttribute('min') || '1', 10);
            const max = parseInt(input.getAttribute('max') || '200', 10);
            let v = parseInt(String(input.value).trim(), 10);
            if (Number.isNaN(v)) v = min;
            v = Math.min(max, Math.max(min, v + step));
            input.value = String(v);
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    const selectAll = document.getElementById('nursery_age_groups_select_all');
    const boxes = document.querySelectorAll('.nursery-age-group-cb');
    if (selectAll && boxes.length) {
        function syncSelectAll() {
            const all = Array.from(boxes).every(function (b) { return b.checked; });
            const some = Array.from(boxes).some(function (b) { return b.checked; });
            selectAll.checked = all;
            selectAll.indeterminate = some && !all;
        }
        selectAll.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = selectAll.checked; });
            selectAll.indeterminate = false;
        });
        boxes.forEach(function (b) { b.addEventListener('change', syncSelectAll); });
        syncSelectAll();
    }
})();
</script>
@endpush
