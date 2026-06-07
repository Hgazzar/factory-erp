@php
    /** @var array<string, mixed> $grid */
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        @if($canManage)
            <button type="button" @click="openModal('weekdays-{{ $scope }}')" class="nursery-btn nursery-btn-soft">
                تعيين أيام الحضور
            </button>
        @else
            <span></span>
        @endif
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('nursery.attendance.index', array_merge(request()->except('week'), ['tab' => $scope === 'children' ? 'children' : 'staff', 'week' => $prevWeek])) }}"
               class="nursery-btn nursery-btn-soft px-3">‹</a>
            <span class="text-sm font-semibold text-orange-950 tabular-nums">{{ $weekLabel }}</span>
            <a href="{{ route('nursery.attendance.index', array_merge(request()->except('week'), ['tab' => $scope === 'children' ? 'children' : 'staff', 'week' => $nextWeek])) }}"
               class="nursery-btn nursery-btn-soft px-3">›</a>
            @if($canManage)
                <button type="button" @click="openModal('leave-{{ $scope }}')" class="nursery-btn nursery-btn-soft">+ تسجيل إجازة</button>
                <button type="button" @click="openModal('report-{{ $scope }}')" class="nursery-btn nursery-btn-primary">إنشاء تقرير</button>
            @endif
        </div>
    </div>

    <form method="get" class="nursery-card p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <input type="hidden" name="tab" value="{{ $scope === 'children' ? 'children' : 'staff' }}">
        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
        <div class="{{ $showClassroomFilter ? '' : 'lg:col-span-2' }}">
            <label class="block text-sm font-semibold text-orange-950 mb-1">بحث</label>
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ $searchPlaceholder }}"
                   class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm">
        </div>
        @if($showClassroomFilter)
            <div>
                <label class="block text-sm font-semibold text-orange-950 mb-1">
                    الفصل <x-info field="nursery.filter_classroom" />
                </label>
                <x-custom-select name="classroom_id" :options="$classroomOptions"
                    :value="(string) ($classroomId ?? '')" :searchable="true" />
            </div>
        @endif
        <button type="submit" class="nursery-btn nursery-btn-soft">تطبيق</button>
    </form>

    <section class="nursery-card overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
            <thead>
                <tr class="bg-orange-50/80 border-b border-orange-100">
                    <th class="px-3 py-3 text-right font-bold text-orange-950 min-w-[10rem] sticky right-0 bg-orange-50/95 z-10">
                        {{ $scope === 'children' ? niche_label('entities.child', 'الطفل') : 'الموظف' }}
                    </th>
                    @foreach($grid['days'] as $day)
                        <th class="px-2 py-3 text-center font-bold text-orange-950 min-w-[5.5rem] {{ $day['date'] === now()->toDateString() ? 'bg-orange-100/80' : '' }}">
                            <div class="text-xs leading-tight">{{ $day['label'] }}</div>
                            @php $summary = collect($grid['day_summaries'])->firstWhere('date', $day['date']); @endphp
                            <div class="text-[10px] font-normal text-orange-700/80 mt-1 tabular-nums">
                                {{ $summary['present'] ?? 0 }}/{{ $summary['total'] ?? 0 }} حاضر
                            </div>
                        </th>
                    @endforeach
                    @if($canManage)
                        <th class="px-2 py-3 w-10"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($grid['rows'] as $row)
                    <tr class="border-b border-orange-50 hover:bg-orange-50/30">
                        <td class="px-3 py-3 sticky right-0 bg-white z-10 border-l border-orange-50">
                            <p class="font-semibold text-orange-950">{{ $row['name'] }}</p>
                            @if($row['subtitle'])
                                <p class="text-xs text-orange-700/70">{{ $row['subtitle'] }}</p>
                            @endif
                            <p class="text-xs text-orange-600 mt-1 tabular-nums">
                                الحضور {{ $row['present_count'] }}/{{ $row['expected_count'] }} أيام
                            </p>
                        </td>
                        @foreach($row['cells'] as $cell)
                            @php
                                $cellClass = match ($cell['state']) {
                                    'present' => 'bg-emerald-50 text-emerald-800',
                                    'leave' => 'bg-amber-50 text-amber-800',
                                    'absent' => 'bg-red-50 text-red-700',
                                    default => 'text-orange-800/50',
                                };
                            @endphp
                            <td class="px-2 py-2 text-center {{ $cellClass }} {{ $cell['date'] === now()->toDateString() ? 'ring-1 ring-inset ring-orange-200' : '' }}">
                                <span class="text-xs font-semibold">{{ $cell['label'] }}</span>
                                @if($cell['detail'])
                                    <div class="text-[10px] opacity-80">{{ $cell['detail'] }}</div>
                                @endif
                            </td>
                        @endforeach
                        @if($canManage)
                            <td class="px-2 py-2 text-center relative" x-data="{ open: false }">
                                <button type="button" @click="open = !open" class="text-orange-700 px-2">⋮</button>
                                <div x-show="open" @click.outside="open = false" x-cloak
                                     class="absolute left-0 top-full mt-1 z-20 nursery-card py-1 min-w-[10rem] text-right shadow-lg">
                                    <button type="button" class="block w-full text-right px-3 py-2 text-sm hover:bg-orange-50"
                                            @click="open=false; report{{ $scope === 'children' ? 'Child' : 'Employee' }}Id='{{ $row['id'] }}'; openModal('report-{{ $scope }}-single')">
                                        إنشاء تقرير
                                    </button>
                                    <button type="button" class="block w-full text-right px-3 py-2 text-sm hover:bg-orange-50"
                                            @click="open=false; leave{{ $scope === 'children' ? 'Child' : 'Employee' }}Id='{{ $row['id'] }}'; openModal('leave-{{ $scope }}-single')">
                                        {{ $scope === 'children' ? 'تعديل إجازات الطفل' : 'تعديل إجازات الموظف' }}
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($grid['days']) + ($canManage ? 2 : 1) }}" class="px-4 py-12 text-center text-orange-800/70">
                            لا توجد سجلات لعرضها في هذا الأسبوع.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
