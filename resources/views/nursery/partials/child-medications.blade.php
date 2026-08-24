@php
    use App\Models\Nursery\ChildMedication;
    $medRows = old('medications');
    if ($medRows === null && isset($child)) {
        $medRows = $child->relationLoaded('medications')
            ? $child->medications->map(fn ($m) => [
                'name' => $m->name,
                'dosage' => $m->dosage,
                'frequency' => $m->frequency,
                'schedule_notes' => $m->schedule_notes,
                'notes' => $m->notes,
            ])->values()->all()
            : [];
    }
    $medRows = is_array($medRows) ? array_values($medRows) : [];
    $medRows = array_values(array_filter($medRows, function ($row): bool {
        if (! is_array($row)) {
            return false;
        }
        foreach (['name', 'dosage', 'frequency', 'schedule_notes', 'notes'] as $field) {
            if (trim((string) ($row[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }));
    $freqOptions = collect(ChildMedication::frequencyOptions())
        ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
        ->values()
        ->all();
@endphp
<div class="space-y-3"
     x-data="{
        meds: @js($medRows),
        freqOptions: @js($freqOptions),
        addMed() {
            this.meds.push({ name: '', dosage: '', frequency: '', schedule_notes: '', notes: '' });
        },
        removeMed(index) {
            this.meds.splice(index, 1);
        },
     }">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-teal-800/80">
            سجّل الأدوية التي يتناولها الطفل داخل الحضانة.
            <x-info field="nursery.child_medications_intro" />
        </p>
        <button type="button" @click="addMed()"
                class="text-sm font-semibold text-teal-600 hover:text-teal-800">+ إضافة دواء</button>
    </div>

    <p x-show="meds.length === 0" x-cloak
       class="text-sm text-teal-700/70 rounded-lg border border-dashed border-teal-200 bg-teal-50/30 px-3 py-2.5">
        لا توجد أدوية مسجّلة. اضغط «+ إضافة دواء» عند الحاجة فقط.
    </p>

    <template x-for="(med, index) in meds" :key="index">
        <div class="p-4 rounded-lg border border-teal-100 bg-teal-50/40 space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm font-bold text-teal-950">دواء <span x-text="index + 1"></span></span>
                <button type="button" @click="removeMed(index)"
                        class="text-xs text-red-600 hover:underline">حذف</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        اسم الدواء
                        <x-info field="nursery.child_medication_name" />
                    </label>
                    <input type="text" :name="'medications[' + index + '][name]'" x-model="med.name"
                           class="w-full rounded-lg border border-teal-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        الجرعة
                        <x-info field="nursery.child_medication_dosage" />
                    </label>
                    <input type="text" :name="'medications[' + index + '][dosage]'" x-model="med.dosage"
                           placeholder="5 مل"
                           class="w-full rounded-lg border border-teal-200 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-teal-950 mb-1 mb-2">
                        التكرار
                        <x-info field="nursery.child_medication_frequency" />
                    </label>
                    <input type="hidden" :name="'medications[' + index + '][frequency]'" x-model="med.frequency">
                    <div class="flex flex-wrap gap-1">
                        <template x-for="opt in freqOptions" :key="opt.value">
                            <button type="button" @click="med.frequency = opt.value"
                                    class="text-xs px-2 py-1 rounded-md border transition"
                                    :class="med.frequency === opt.value
                                        ? 'bg-teal-500 text-white border-teal-600'
                                        : 'bg-white text-teal-900 border-teal-200 hover:bg-teal-50'"
                                    x-text="opt.label"></button>
                        </template>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        وقت/طريقة الإعطاء
                        <x-info field="nursery.child_medication_schedule" />
                    </label>
                    <input type="text" :name="'medications[' + index + '][schedule_notes]'" x-model="med.schedule_notes"
                           placeholder="بعد الغداء"
                           class="w-full rounded-lg border border-teal-200 px-3 py-2">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-semibold text-teal-950 mb-1">
                        ملاحظة
                        <x-info field="nursery.child_medication_notes" />
                    </label>
                    <input type="text" :name="'medications[' + index + '][notes]'" x-model="med.notes"
                           class="w-full rounded-lg border border-teal-200 px-3 py-2">
                </div>
            </div>
        </div>
    </template>
</div>
