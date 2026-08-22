{{--
    جدول أدوية للقراءة فقط — يُستخدم في الإدارة وبوابة ولي الأمر.
    @var \Illuminate\Support\Collection<int, \App\Models\Nursery\ChildMedication>|\Illuminate\Database\Eloquent\Collection $medications
--}}
@if($medications->isEmpty())
    <p class="text-sm text-orange-700/70">{{ $emptyMessage ?? 'لا توجد أدوية مسجّلة.' }}</p>
@else
    <div class="overflow-x-auto -mx-1">
        <table class="nursery-table min-w-[520px]">
            <thead>
                <tr>
                    <th>الدواء <x-info field="nursery.child_medication_name" /></th>
                    <th>الجرعة <x-info field="nursery.child_medication_dosage" /></th>
                    <th>التكرار <x-info field="nursery.child_medication_frequency" /></th>
                    <th>الإعطاء <x-info field="nursery.child_medication_schedule" /></th>
                    <th>ملاحظة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medications as $med)
                    <tr>
                        <td>
                            <span class="nursery-table-name__title">{{ $med->name }}</span>
                        </td>
                        <td>{{ $med->dosage ?: '—' }}</td>
                        <td>{{ $med->frequencyLabel() }}</td>
                        <td>{{ $med->schedule_notes ?: '—' }}</td>
                        <td>{{ $med->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
