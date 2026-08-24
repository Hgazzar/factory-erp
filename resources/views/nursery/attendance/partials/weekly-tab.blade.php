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
            <span class="text-sm font-semibold text-teal-950 tabular-nums">{{ $weekLabel }}</span>
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
            <label class="block text-sm font-semibold text-teal-950 mb-1">بحث</label>
            <input type="search" name="q" value="{{ $q }}" placeholder="{{ $searchPlaceholder }}"
                   class="w-full rounded-lg border border-teal-200 px-3 py-2 text-sm">
        </div>
        @if($showClassroomFilter)
            <div>
                <label class="block text-sm font-semibold text-teal-950 mb-1">
                    الفصل <x-info field="nursery.filter_classroom" />
                </label>
                <x-custom-select name="classroom_id" :options="$classroomOptions"
                    :value="(string) ($classroomId ?? '')" :searchable="true" />
            </div>
        @endif
        <button type="submit" class="nursery-btn nursery-btn-soft">تطبيق</button>
    </form>

    <section class="nursery-card nursery-table-card">
        <div class="nursery-table-card__toolbar">
            <div>
                <h2>الحضور الأسبوعي — {{ $scope === 'children' ? niche_label('entities.child', 'الأطفال') : 'طاقم العمل' }}</h2>
                <p>{{ $weekLabel }} · {{ count($grid['rows']) }} صف</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="nursery-table nursery-table--grid min-w-[900px]">
                <thead>
                    <tr>
                        <th class="nursery-table__sticky min-w-[10rem]">
                            {{ $scope === 'children' ? niche_label('entities.child', 'الطفل') : 'الموظف' }}
                        </th>
                        @foreach($grid['days'] as $day)
                            <th class="text-center min-w-[5.5rem] {{ $day['date'] === now()->toDateString() ? '!bg-teal-50' : '' }}">
                                <div class="text-xs leading-tight font-bold text-slate-700">{{ $day['label'] }}</div>
                                @php $summary = collect($grid['day_summaries'])->firstWhere('date', $day['date']); @endphp
                                <div class="text-[10px] font-normal text-slate-400 mt-1 tabular-nums">
                                    {{ $summary['present'] ?? 0 }}/{{ $summary['total'] ?? 0 }} حاضر
                                </div>
                            </th>
                        @endforeach
                        @if($canManage)
                            <th class="text-center w-14">إجراءات</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($grid['rows'] as $row)
                        <tr>
                            <td class="nursery-table__sticky border-l border-slate-100">
                                <span class="nursery-table-name__title">{{ $row['name'] }}</span>
                                @if($row['subtitle'])
                                    <span class="nursery-table-name__sub">{{ $row['subtitle'] }}</span>
                                @endif
                                <span class="block text-xs text-teal-600 mt-1 tabular-nums font-semibold">
                                    الحضور {{ $row['present_count'] }}/{{ $row['expected_count'] }} أيام
                                </span>
                            </td>
                            @foreach($row['cells'] as $cell)
                                @php
                                    $cellClass = match ($cell['state']) {
                                        'present' => 'bg-emerald-50 text-emerald-800',
                                        'leave' => 'bg-amber-50 text-amber-800',
                                        'absent' => 'bg-red-50 text-red-700',
                                        default => 'text-slate-400',
                                    };
                                @endphp
                                <td class="text-center {{ $cellClass }} {{ $cell['date'] === now()->toDateString() ? 'ring-1 ring-inset ring-teal-200' : '' }}">
                                    <span class="text-xs font-semibold">{{ $cell['label'] }}</span>
                                    @if($cell['detail'])
                                        <div class="text-[10px] opacity-80">{{ $cell['detail'] }}</div>
                                    @endif
                                </td>
                            @endforeach
                            @if($canManage)
                                {{-- Alpine dropdown (not erp-actions): menu must stay in Alpine scope for openModal / ids --}}
                                <td class="text-center relative" x-data="{ open: false }">
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
                                            title="المزيد من الإجراءات"
                                            aria-label="المزيد من الإجراءات"
                                            :aria-expanded="open.toString()">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak
                                         class="absolute left-0 top-full z-30 mt-2 min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
                                         role="menu"
                                         dir="rtl">
                                        <button type="button"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-teal-50"
                                                role="menuitem"
                                                @click="open=false; report{{ $scope === 'children' ? 'Child' : 'Employee' }}Id='{{ $row['id'] }}'; openModal('report-{{ $scope }}-single')">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">إنشاء تقرير</span>
                                        </button>
                                        <button type="button"
                                                class="erp-menu-item flex w-full items-center gap-3 px-3 py-2.5 text-right text-sm font-medium text-gray-800 transition hover:bg-teal-50"
                                                role="menuitem"
                                                @click="open=false; leave{{ $scope === 'children' ? 'Child' : 'Employee' }}Id='{{ $row['id'] }}'; openModal('leave-{{ $scope }}-single')">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/></svg>
                                            </span>
                                            <span class="flex-1 leading-snug">{{ $scope === 'children' ? 'تعديل إجازات الطفل' : 'تعديل إجازات الموظف' }}</span>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($grid['days']) + ($canManage ? 2 : 1) }}" class="!py-12 text-center text-teal-800/70">
                                لا توجد سجلات لعرضها في هذا الأسبوع.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
