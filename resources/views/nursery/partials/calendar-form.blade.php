@php
    /** @var \App\Models\Nursery\CalendarEntry|null $entry */
    $entry = $entry ?? null;
    $entryType = $entryType ?? ($entry?->entry_type ?? 'lesson');
    $formAction = $formAction ?? '';
    $formMethod = $formMethod ?? 'POST';
    $submitLabel = $submitLabel ?? 'إضافة';
    $selectedClassroomIds = array_values(array_map('intval', is_array($selectedClassroomIds ?? null) ? $selectedClassroomIds : []));
    $selectedChildIds = array_values(array_map('intval', is_array($selectedChildIds ?? null) ? $selectedChildIds : []));
    $eventDate = old('event_date', $entry?->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $startsTime = old('starts_at_time', $entry?->starts_at?->format('H:i') ?? '09:00');
    $endsTime = old('ends_at_time', $entry?->ends_at?->format('H:i') ?? '10:00');
    $isRecurring = old('is_recurring', $entry?->is_recurring ?? false);
    $showTypePicker = $entry === null && ! request()->has('type');
@endphp

<div class="w-full space-y-5" dir="rtl">
    @if($showTypePicker)
        <section class="nursery-card p-5 space-y-4">
            <h2 class="text-lg font-bold text-teal-950">ماذا تريد أن تضيف؟</h2>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach($typeLabels as $typeKey => $typeLabel)
                    <a href="{{ route('nursery.calendar.create', ['type' => $typeKey]) }}"
                       class="nursery-card p-4 text-center hover:ring-2 hover:ring-teal-400 transition {{ $entryType === $typeKey ? 'ring-2 ring-teal-500' : '' }}">
                        <p class="font-bold text-teal-950 text-lg">{{ $typeLabel }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <form method="post" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-5" id="nurseryCalendarForm">
        @csrf
        @if(strtoupper($formMethod) !== 'POST')
            @method($formMethod)
        @endif
        <input type="hidden" name="entry_type" value="{{ $entryType }}">

        <section class="nursery-card p-5 space-y-4">
            <h2 class="text-lg font-bold text-teal-950 border-b border-teal-100 pb-2">
                @if($entryType === 'lesson')
                    بيانات الدرس
                @elseif($entryType === 'activity')
                    بيانات النشاط
                @else
                    بيانات الإعلان
                @endif
            </h2>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        التاريخ <span class="text-red-600">*</span>
                        <x-info field="nursery.calendar_date" />
                    </label>
                    <input type="date" name="event_date" value="{{ $eventDate }}" required
                           class="w-full rounded-lg border border-teal-200 px-3 py-2">
                    @error('event_date')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-teal-950 mb-1">
                            من <span class="text-red-600">*</span>
                            <x-info field="nursery.calendar_time_from" />
                        </label>
                        <input type="time" name="starts_at_time" value="{{ $startsTime }}" required
                               class="w-full rounded-lg border border-teal-200 px-3 py-2">
                        @error('starts_at_time')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-teal-950 mb-1">
                            إلى <span class="text-red-600">*</span>
                            <x-info field="nursery.calendar_time_to" />
                        </label>
                        <input type="time" name="ends_at_time" value="{{ $endsTime }}" required
                               class="w-full rounded-lg border border-teal-200 px-3 py-2">
                        @error('ends_at_time')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                @if($entryType === 'lesson')
                    <div>
                        <label class="block text-sm font-semibold text-teal-950 mb-1">
                            اسم الوحدة <span class="text-red-600">*</span>
                            <x-info field="nursery.calendar_unit" />
                        </label>
                        <x-custom-select name="unit_id" id="calendar_unit_select" :options="$unitOptions"
                            :value="old('unit_id', (string) ($entry?->unit_id ?? ''))"
                            empty-label="— الوحدة —"
                            placeholder="ابحث عن الوحدة..." :searchable="true" />
                        @error('unit_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-teal-950 mb-1">
                            اسم الدرس <span class="text-red-600">*</span>
                            <x-info field="nursery.calendar_lesson" />
                        </label>
                        <x-custom-select name="unit_lesson_id" id="calendar_lesson_select" :options="$lessonOptions"
                            :value="old('unit_lesson_id', (string) ($entry?->unit_lesson_id ?? ''))"
                            empty-label="— الدرس —"
                            placeholder="ابحث عن الدرس..." :searchable="true" />
                        <input type="hidden" name="title" id="calendar_lesson_title_fallback"
                               value="{{ old('title', $entry?->title) }}">
                        @error('unit_lesson_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @else
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-semibold text-teal-950 mb-1">
                            العنوان <span class="text-red-600">*</span>
                            <x-info field="nursery.calendar_title" />
                        </label>
                        <input type="text" name="title" value="{{ old('title', $entry?->title) }}" required
                               class="w-full rounded-lg border border-teal-200 px-3 py-2"
                               placeholder="{{ $entryType === 'activity' ? 'اسم النشاط' : 'عنوان الإعلان' }}">
                        @error('title')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        حدد الفصول
                        <x-info field="nursery.calendar_classrooms" />
                    </label>
                    <div class="nursery-age-group-list max-h-[min(16rem,40vh)] overflow-y-auto">
                        @forelse($classrooms as $room)
                            <label class="nursery-age-row {{ $loop->iteration % 2 === 0 ? 'nursery-age-row--stripe' : '' }}">
                                <input type="checkbox" name="classroom_ids[]" value="{{ $room->id }}"
                                       class="rounded border-teal-300 text-teal-600 focus:ring-teal-500 shrink-0"
                                       @checked(in_array((int) $room->id, $selectedClassroomIds, true))>
                                <span class="flex-1">{{ $room->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-teal-800/70 p-3">لا توجد فصول نشطة.</p>
                        @endforelse
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        أطفال محددون (اختياري)
                        <x-info field="nursery.calendar_children" />
                    </label>
                    <div class="nursery-age-group-list max-h-[min(16rem,40vh)] overflow-y-auto">
                        @forelse($children as $child)
                            <label class="nursery-age-row {{ $loop->iteration % 2 === 0 ? 'nursery-age-row--stripe' : '' }}">
                                <input type="checkbox" name="child_ids[]" value="{{ $child->id }}"
                                       class="rounded border-teal-300 text-teal-600 focus:ring-teal-500 shrink-0"
                                       @checked(in_array((int) $child->id, $selectedChildIds, true))>
                                <span class="flex-1">{{ $child->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-teal-800/70 p-3">لا يوجد أطفال نشطون.</p>
                        @endforelse
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        ملاحظات
                        <x-info field="nursery.calendar_notes" />
                    </label>
                    <textarea name="notes" rows="4" maxlength="5000" placeholder="اكتب شيئاً..."
                              class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">{{ old('notes', $entry?->notes) }}</textarea>
                    @error('notes')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <label class="nursery-switch shrink-0">
                        <input type="checkbox" name="is_recurring" value="1" class="nursery-switch-input" @checked($isRecurring)>
                        <span class="nursery-switch-track" aria-hidden="true"></span>
                    </label>
                    <span class="text-sm font-semibold text-teal-950">
                        تكرار
                        <x-info field="nursery.calendar_repeat" />
                    </span>
                </div>
            </div>
        </section>

        @if($entryType !== 'activity')
            <section class="nursery-card p-5 space-y-3">
                <h2 class="text-lg font-bold text-teal-950">
                    صور
                    <x-info field="nursery.calendar_media" />
                </h2>

                @if($entry !== null && $entry->relationLoaded('attachments') && $entry->attachments->isNotEmpty())
                    <x-attachment-handler
                        theme="tailwind"
                        hint-field="nursery.calendar_media"
                        title="الصور الحالية"
                        :existing="$entry->attachments"
                        :uploadable="false"
                        :allow-delete="true"
                        accept=".jpg,.jpeg,.png,.gif,.webp"
                        help-text="يمكنك حذف صورة قديمة. لإضافة صور جديدة استخدم حقل الرفع أدناه."
                    />
                @endif

                @if($entry !== null && is_array($entry->media_links) && $entry->media_links !== [])
                    <div class="rounded-lg border border-amber-100 bg-amber-50/50 p-3 space-y-1">
                        <p class="text-xs font-semibold text-amber-900">روابط قديمة (للمرجع فقط — استخدم رفع الصور أعلاه)</p>
                        @foreach($entry->media_links as $link)
                            @if(! empty($link['url']))
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="block text-sm text-teal-700 underline truncate" dir="ltr">
                                    {{ $link['label'] ?? $link['url'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        رفع صور
                        <x-info field="nursery.calendar_media_upload" />
                    </label>
                    <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp"
                           class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm bg-white">
                    @error('images')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('images.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-teal-700/70 mt-1">يمكن اختيار أكثر من صورة (jpg, png, webp, gif — حتى 5 ميجا للصورة).</p>
                </div>
            </section>
        @endif

    </form>

    <div class="flex flex-wrap gap-2 items-center">
        <button type="submit" form="nurseryCalendarForm" class="nursery-btn nursery-btn-primary">{{ $submitLabel }}</button>
        <a href="{{ route('nursery.calendar.index') }}" class="nursery-btn nursery-btn-soft">إلغاء</a>
        @if($entry !== null && app(\App\Support\NurseryAccess::class)->allows(\App\Support\NurseryAccess::CAP_MANAGE_CALENDAR))
            <form method="post" action="{{ route('nursery.calendar.destroy', $entry) }}" class="ms-auto"
                  onsubmit="return confirm('حذف هذا الإدخال من التقويم؟')">
                @csrf
                @method('DELETE')
                <button type="submit" class="nursery-btn nursery-btn-soft text-red-700 border-red-200">حذف</button>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('nurseryCalendarForm');
    if (!form || form.dataset.calendarBound === '1') return;
    form.dataset.calendarBound = '1';

    var unitSelect = document.getElementById('calendar_unit_select');
    var lessonsUrl = @json(route('nursery.calendar.lessons'));

    function setSelectOptions(selectName, options) {
        var hidden = form.querySelector('input[name="' + selectName + '"]');
        if (!hidden) return;
        var root = hidden.closest('[x-data]');
        if (!root || !root._x_dataStack) return;
        var data = root._x_dataStack[0];
        var items = [{ v: '', l: '—' }];
        options.forEach(function (o) {
            items.push({ v: String(o.value), l: o.label });
        });
        data.items = items;
        data.selected = '';
        hidden.value = '';
    }

    function reloadLessons(unitId) {
        if (!unitId) {
            setSelectOptions('unit_lesson_id', []);
            return;
        }
        fetch(lessonsUrl + '?unit_id=' + encodeURIComponent(unitId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (options) { setSelectOptions('unit_lesson_id', options); })
            .catch(function () {});
    }

    document.addEventListener('custom-select-change', function (e) {
        if (e.detail && e.detail.name === 'unit_id') {
            reloadLessons(e.detail.value);
        }
    });

})();
</script>
@endpush
