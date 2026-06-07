@php
    /** @var array<string, mixed> $staffBoard */
@endphp
<div class="grid gap-6 lg:grid-cols-3">
    <section class="nursery-card p-4 lg:col-span-1">
        <h2 class="font-bold text-amber-700 mb-2">بانتظار الحضور ({{ $staffBoard['not_yet']->count() }})</h2>
        @forelse($staffBoard['not_yet'] as $employee)
            <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-orange-50 text-sm">
                <span class="font-medium">{{ $employee->name }}</span>
                <form method="post" action="{{ route('nursery.attendance.staff.check-in') }}">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    <button type="submit" class="nursery-btn nursery-btn-primary text-xs py-1 px-2">حضور</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-orange-700/60">—</p>
        @endforelse
    </section>
    <section class="nursery-card p-4 lg:col-span-1">
        <h2 class="font-bold text-emerald-700 mb-2">حاضرون ({{ $staffBoard['checked_in']->count() }})</h2>
        @forelse($staffBoard['checked_in'] as $row)
            <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-orange-50 text-sm">
                <div>
                    <span class="font-medium">{{ $row['employee']->name }}</span>
                    <span class="text-xs text-emerald-600 ms-1">{{ $row['log']->checked_in_at?->format('H:i') }}</span>
                </div>
                <form method="post" action="{{ route('nursery.attendance.staff.check-out') }}">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $row['employee']->id }}">
                    <button type="submit" class="nursery-btn nursery-btn-soft text-xs py-1 px-2">انصراف</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-orange-700/60">—</p>
        @endforelse
    </section>
    <section class="nursery-card p-4 lg:col-span-1">
        <h2 class="font-bold text-sky-700 mb-2">انصرفوا ({{ $staffBoard['checked_out']->count() }})</h2>
        @forelse($staffBoard['checked_out'] as $row)
            <div class="py-2 border-b border-orange-50 text-sm">
                <span class="font-medium">{{ $row['employee']->name }}</span>
                <span class="text-xs text-sky-600 ms-1">{{ $row['log']->checked_out_at?->format('H:i') }}</span>
            </div>
        @empty
            <p class="text-sm text-orange-700/60">—</p>
        @endforelse
    </section>
</div>
