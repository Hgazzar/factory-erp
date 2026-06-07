<section class="space-y-4" x-data="{ showAdd: {{ $errors->has('shifts') ? 'true' : 'false' }}, rows: [{ name: '', start_time: '', end_time: '' }] }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-orange-950">إدارة المناوبات <x-info field="nursery.settings_shifts_intro" /></h2>
        @if($canManage)
            <button type="button" @click="showAdd = true" class="nursery-btn nursery-btn-primary text-sm">+ إضافة مناوبات</button>
        @endif
    </div>

    <div class="nursery-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[480px]">
                <thead>
                    <tr class="bg-orange-50/80 border-b border-orange-100">
                        <th class="px-4 py-3 text-right font-bold text-orange-950">اسم المناوبة <x-info field="nursery.settings_shift_name" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">وقت البداية <x-info field="nursery.settings_shift_start" /></th>
                        <th class="px-4 py-3 text-right font-bold text-orange-950">وقت النهاية <x-info field="nursery.settings_shift_end" /></th>
                        @if($canManage)<th class="px-4 py-3 w-20"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr class="border-b border-orange-50 hover:bg-orange-50/40">
                            <td class="px-4 py-3 font-semibold">{{ $shift->name }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $shift->start_time?->format('H:i') }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $shift->end_time?->format('H:i') }}</td>
                            @if($canManage)
                                <td class="px-4 py-3 text-left">
                                    <form method="POST" action="{{ route('nursery.settings.shifts.destroy', $shift) }}" onsubmit="return confirm('حذف هذه المناوبة؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 text-xs font-semibold hover:underline">حذف</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 4 : 3 }}" class="px-4 py-10 text-center text-orange-700/70">لا توجد مناوبات — أضف مناوبة عمل.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($canManage)
        <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @keydown.escape.window="showAdd = false">
            <div class="nursery-card w-full max-w-lg max-h-[90vh] overflow-y-auto p-5 space-y-4" @click.outside="showAdd = false">
                <h3 class="text-lg font-bold text-orange-950">إضافة مناوبات</h3>
                <form method="POST" action="{{ route('nursery.settings.shifts.store') }}" class="space-y-4">
                    @csrf
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="p-4 rounded-lg border border-orange-100 bg-orange-50/30 space-y-3">
                            <div>
                                <label class="block text-sm font-semibold text-orange-950 mb-1">اسم المناوبة * <x-info field="nursery.settings_shift_name" /></label>
                                <input type="text" :name="'shifts[' + index + '][name]'" x-model="row.name" required class="w-full rounded-lg border border-orange-200 px-3 py-2">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-semibold text-orange-950 mb-1">وقت البداية * <x-info field="nursery.settings_shift_start" /></label>
                                    <input type="time" :name="'shifts[' + index + '][start_time]'" x-model="row.start_time" required class="w-full rounded-lg border border-orange-200 px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-orange-950 mb-1">وقت النهاية * <x-info field="nursery.settings_shift_end" /></label>
                                    <input type="time" :name="'shifts[' + index + '][end_time]'" x-model="row.end_time" required class="w-full rounded-lg border border-orange-200 px-3 py-2">
                                </div>
                            </div>
                        </div>
                    </template>
                    @error('shifts')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <button type="button" @click="rows.push({ name: '', start_time: '', end_time: '' })"
                            class="text-sm font-semibold text-orange-600 hover:text-orange-800">+ إضافة حقل جديد</button>
                    <div class="flex flex-wrap gap-2 pt-2">
                        <button type="submit" class="nursery-btn nursery-btn-primary">حفظ</button>
                        <button type="button" @click="showAdd = false" class="nursery-btn nursery-btn-soft">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</section>
