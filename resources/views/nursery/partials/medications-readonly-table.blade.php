{{--
    جدول أدوية للقراءة فقط — يُستخدم في الإدارة وبوابة ولي الأمر.
    @var \Illuminate\Support\Collection<int, \App\Models\Nursery\ChildMedication>|\Illuminate\Database\Eloquent\Collection $medications
--}}
@if($medications->isEmpty())
    <p class="text-sm text-orange-700/70">{{ $emptyMessage ?? 'لا توجد أدوية مسجّلة.' }}</p>
@else
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[520px]">
            <thead>
                <tr class="bg-orange-50/80 border-b border-orange-100">
                    <th class="px-3 py-2 text-right font-bold">الدواء <x-info field="nursery.child_medication_name" /></th>
                    <th class="px-3 py-2 text-right font-bold">الجرعة <x-info field="nursery.child_medication_dosage" /></th>
                    <th class="px-3 py-2 text-right font-bold">التكرار <x-info field="nursery.child_medication_frequency" /></th>
                    <th class="px-3 py-2 text-right font-bold">الإعطاء <x-info field="nursery.child_medication_schedule" /></th>
                    <th class="px-3 py-2 text-right font-bold">ملاحظة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medications as $med)
                    <tr class="border-b border-orange-50">
                        <td class="px-3 py-2 font-semibold">{{ $med->name }}</td>
                        <td class="px-3 py-2">{{ $med->dosage ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $med->frequencyLabel() }}</td>
                        <td class="px-3 py-2">{{ $med->schedule_notes ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $med->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
