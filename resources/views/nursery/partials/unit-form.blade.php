@php
    /** @var \App\Models\Nursery\Unit|null $unit */
    $unit = $unit ?? null;
    $formAction = $formAction ?? '';
    $formMethod = $formMethod ?? 'POST';
    $submitLabel = $submitLabel ?? 'حفظ';
    $selectedAgeGroups = old('age_groups', $unit?->age_groups ?? []);
    if (! is_array($selectedAgeGroups)) {
        $selectedAgeGroups = [];
    }
    $goalLines = old('goals', $unit?->goalLines() ?? ['']);
    if (! is_array($goalLines) || $goalLines === []) {
        $goalLines = [''];
    }
    $lessonLines = old('lessons', $unit ? $unit->activeLessons->pluck('name')->all() : ['']);
    if (! is_array($lessonLines) || $lessonLines === []) {
        $lessonLines = [''];
    }
@endphp

<form method="post" action="{{ $formAction }}" class="space-y-5" id="nurseryUnitForm">
    @csrf
    @if(strtoupper($formMethod) !== 'POST')
        @method($formMethod)
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="nursery-card p-5 space-y-4 lg:col-span-2">
            <h2 class="text-lg font-bold text-orange-950 border-b border-orange-100 pb-2">بيانات الوحدة</h2>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        اسم الوحدة <span class="text-red-600">*</span>
                        <x-info field="nursery.unit_name" />
                    </label>
                    <input type="text" name="name" value="{{ old('name', $unit?->name) }}" required
                           class="w-full rounded-lg border border-orange-200 px-3 py-2"
                           placeholder="مثال: الحيوانات الأليفة">
                    @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-orange-950 mb-1">
                        الفئة العمرية <span class="text-red-600">*</span>
                        <x-info field="nursery.unit_age_groups" />
                    </label>
                    <div class="nursery-age-group-list max-h-[min(22rem,55vh)] overflow-y-auto">
                        <label class="nursery-age-row nursery-age-row--header">
                            <input type="checkbox" id="nursery_unit_age_groups_select_all" class="rounded border-orange-300 text-orange-600 focus:ring-orange-500 shrink-0">
                            <span class="flex-1">تحديد الكل</span>
                        </label>
                        @foreach($ageGroupLabels as $key => $label)
                            <label class="nursery-age-row {{ $loop->iteration % 2 === 0 ? 'nursery-age-row--stripe' : '' }}">
                                <input type="checkbox" name="age_groups[]" value="{{ $key }}"
                                       class="nursery-unit-age-group-cb rounded border-orange-300 text-orange-600 focus:ring-orange-500 shrink-0"
                                       @checked(in_array($key, $selectedAgeGroups, true))>
                                <span class="flex-1 leading-snug">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('age_groups')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('age_groups.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <label class="text-sm font-semibold text-orange-950">
                            أهداف الوحدة <span class="text-red-600">*</span>
                            <x-info field="nursery.unit_goals" />
                        </label>
                        <button type="button" id="nursery_unit_add_goal" class="nursery-btn nursery-btn-soft text-xs py-1.5">+ إضافة هدف</button>
                    </div>
                    <div id="nursery_unit_goals_list" class="space-y-2">
                        @foreach($goalLines as $index => $goalText)
                            <div class="nursery-unit-goal-row flex gap-2 items-start">
                                <span class="text-sm text-orange-800/70 pt-2 tabular-nums w-6 shrink-0">{{ $loop->iteration }}.</span>
                                <input type="text" name="goals[]" value="{{ $goalText }}"
                                       class="flex-1 rounded-lg border border-orange-200 px-3 py-2 text-sm"
                                       placeholder="مثال: التعرف على أصوات الحيوانات">
                                <button type="button" class="nursery-unit-remove-goal nursery-btn nursery-btn-soft text-xs py-2 px-2 shrink-0" title="حذف">×</button>
                            </div>
                        @endforeach
                    </div>
                    @error('goals')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('goals.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <label class="text-sm font-semibold text-orange-950">
                            دروس الوحدة
                            <x-info field="nursery.unit_lessons" />
                        </label>
                        <button type="button" id="nursery_unit_add_lesson" class="nursery-btn nursery-btn-soft text-xs py-1.5">+ إضافة درس</button>
                    </div>
                    <div id="nursery_unit_lessons_list" class="space-y-2">
                        @foreach($lessonLines as $lessonText)
                            <div class="nursery-unit-lesson-row flex gap-2 items-start">
                                <input type="text" name="lessons[]" value="{{ $lessonText }}"
                                       class="flex-1 rounded-lg border border-orange-200 px-3 py-2 text-sm"
                                       placeholder="مثال: التعرف على أصوات الحيوانات">
                                <button type="button" class="nursery-unit-remove-lesson nursery-btn nursery-btn-soft text-xs py-2 px-2 shrink-0" title="حذف">×</button>
                            </div>
                        @endforeach
                    </div>
                    @error('lessons.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                @if($unit !== null)
                    <div class="max-w-xs">
                        <label class="block text-sm font-semibold text-orange-950 mb-1">
                            حالة الوحدة
                            <x-info field="nursery.unit_status" />
                        </label>
                        <x-custom-select name="is_active"
                            :options="[['value' => 'active', 'label' => 'نشطة'], ['value' => 'inactive', 'label' => 'مؤرشفة']]"
                            :value="old('is_active', $unit->is_active ? 'active' : 'inactive')"
                            :searchable="false" />
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="flex flex-wrap gap-2">
        <button type="submit" name="submit_action" value="save" class="nursery-btn nursery-btn-primary">{{ $submitLabel }}</button>
        @if($unit === null)
            <button type="submit" name="submit_action" value="save_and_details" class="nursery-btn nursery-btn-soft border-orange-300">حفظ وإكمال التفاصيل</button>
            <button type="submit" name="submit_action" value="save_and_new" class="nursery-btn nursery-btn-soft border-orange-300">حفظ وإضافة جديد</button>
        @endif
        <a href="{{ route('nursery.units.index') }}" class="nursery-btn nursery-btn-soft">إلغاء</a>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('nurseryUnitForm');
    if (!form || form.dataset.unitBound === '1') return;
    form.dataset.unitBound = '1';

    const selectAll = document.getElementById('nursery_unit_age_groups_select_all');
    const boxes = form.querySelectorAll('.nursery-unit-age-group-cb');
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

    const goalsList = document.getElementById('nursery_unit_goals_list');
    const addBtn = document.getElementById('nursery_unit_add_goal');

    function renumberGoals() {
        if (!goalsList) return;
        goalsList.querySelectorAll('.nursery-unit-goal-row').forEach(function (row, idx) {
            const num = row.querySelector('span');
            if (num) num.textContent = (idx + 1) + '.';
        });
    }

    function bindRemove(btn) {
        btn.addEventListener('click', function () {
            const rows = goalsList.querySelectorAll('.nursery-unit-goal-row');
            if (rows.length <= 1) {
                rows[0].querySelector('input').value = '';
                return;
            }
            btn.closest('.nursery-unit-goal-row').remove();
            renumberGoals();
        });
    }

    goalsList.querySelectorAll('.nursery-unit-remove-goal').forEach(bindRemove);

    if (addBtn && goalsList) {
        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'nursery-unit-goal-row flex gap-2 items-start';
            row.innerHTML = '<span class="text-sm text-orange-800/70 pt-2 tabular-nums w-6 shrink-0"></span>'
                + '<input type="text" name="goals[]" class="flex-1 rounded-lg border border-orange-200 px-3 py-2 text-sm" placeholder="هدف جديد">'
                + '<button type="button" class="nursery-unit-remove-goal nursery-btn nursery-btn-soft text-xs py-2 px-2 shrink-0" title="حذف">×</button>';
            goalsList.appendChild(row);
            bindRemove(row.querySelector('.nursery-unit-remove-goal'));
            renumberGoals();
            row.querySelector('input').focus();
        });
    }

    const lessonsList = document.getElementById('nursery_unit_lessons_list');
    const addLessonBtn = document.getElementById('nursery_unit_add_lesson');

    function bindRemoveLesson(btn) {
        btn.addEventListener('click', function () {
            const rows = lessonsList.querySelectorAll('.nursery-unit-lesson-row');
            if (rows.length <= 1) {
                rows[0].querySelector('input').value = '';
                return;
            }
            btn.closest('.nursery-unit-lesson-row').remove();
        });
    }

    if (lessonsList) {
        lessonsList.querySelectorAll('.nursery-unit-remove-lesson').forEach(bindRemoveLesson);
    }

    if (addLessonBtn && lessonsList) {
        addLessonBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'nursery-unit-lesson-row flex gap-2 items-start';
            row.innerHTML = '<input type="text" name="lessons[]" class="flex-1 rounded-lg border border-orange-200 px-3 py-2 text-sm" placeholder="اسم الدرس">'
                + '<button type="button" class="nursery-unit-remove-lesson nursery-btn nursery-btn-soft text-xs py-2 px-2 shrink-0" title="حذف">×</button>';
            lessonsList.appendChild(row);
            bindRemoveLesson(row.querySelector('.nursery-unit-remove-lesson'));
            row.querySelector('input').focus();
        });
    }
})();
</script>
@endpush
