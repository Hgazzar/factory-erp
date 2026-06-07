<form method="get" class="nursery-card p-4 flex flex-wrap gap-3 items-end">
    <input type="hidden" name="tab" value="register">
    <div class="flex-1 min-w-[200px]">
        <label class="block text-sm font-semibold text-orange-950 mb-1">
            بحث سريع
            <x-info field="nursery.filter_attendance_person" />
        </label>
        <input type="search" name="q" value="{{ $q }}"
               class="w-full rounded-lg border border-orange-200 px-3 py-2 text-sm"
               placeholder="اسم الطفل أو الموظف، الكود، أو الهاتف">
    </div>
    <button type="submit" class="nursery-btn nursery-btn-primary">بحث</button>
</form>

@if($childSearchResults->isNotEmpty())
    <section class="nursery-card p-4">
        <h2 class="font-bold text-orange-950 mb-3">👶 أطفال — نتائج البحث</h2>
        <div class="space-y-2">
            @foreach($childSearchResults as $child)
                <div class="flex flex-wrap items-center justify-between gap-2 p-3 rounded-xl bg-orange-50 border border-orange-100">
                    <div>
                        <p class="font-semibold">{{ $child->name }}</p>
                        <p class="text-xs text-orange-800/70">{{ $child->code }} · {{ $child->guardian?->phone }}</p>
                    </div>
                    <div class="flex gap-2">
                        <form method="post" action="{{ route('nursery.attendance.check-in') }}">@csrf<input type="hidden" name="child_id" value="{{ $child->id }}"><button type="submit" class="nursery-btn nursery-btn-primary text-sm">حضور</button></form>
                        <form method="post" action="{{ route('nursery.attendance.check-out') }}">@csrf<input type="hidden" name="child_id" value="{{ $child->id }}"><button type="submit" class="nursery-btn nursery-btn-soft text-sm">انصراف</button></form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

@if($staffSearchResults->isNotEmpty())
    <section class="nursery-card p-4">
        <h2 class="font-bold text-orange-950 mb-3">👥 طاقم العمل — نتائج البحث</h2>
        <div class="space-y-2">
            @foreach($staffSearchResults as $employee)
                <div class="flex flex-wrap items-center justify-between gap-2 p-3 rounded-xl bg-orange-50 border border-orange-100">
                    <div><p class="font-semibold">{{ $employee->name }}</p><p class="text-xs text-orange-800/70">{{ $employee->code }}</p></div>
                    <div class="flex gap-2">
                        <form method="post" action="{{ route('nursery.attendance.staff.check-in') }}">@csrf<input type="hidden" name="employee_id" value="{{ $employee->id }}"><button type="submit" class="nursery-btn nursery-btn-primary text-sm">حضور</button></form>
                        <form method="post" action="{{ route('nursery.attendance.staff.check-out') }}">@csrf<input type="hidden" name="employee_id" value="{{ $employee->id }}"><button type="submit" class="nursery-btn nursery-btn-soft text-sm">انصراف</button></form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

<section class="space-y-3">
    <div class="flex flex-wrap items-center gap-2">
        <h2 class="text-lg font-bold text-orange-950">👶 لوحة اليوم — {{ niche_label('entities.child', 'الأطفال') }}</h2>
        <x-info field="nursery.quick_check_in" />
        <span class="text-xs text-orange-700/70">({{ $board['date'] }})</span>
    </div>
    @include('nursery.partials.today-board', ['board' => $board])
</section>

@if($staffBoard !== null)
    <section class="space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-lg font-bold text-orange-950">👥 لوحة اليوم — طاقم العمل</h2>
            <x-info field="nursery.staff_quick_attendance" />
            <span class="text-xs text-orange-700/70">({{ $staffBoard['date'] }})</span>
        </div>
        @include('nursery.partials.staff-today-board', ['staffBoard' => $staffBoard])
    </section>
@endif

<p class="text-xs text-orange-700/70">
    للمتابعة الأسبوعية والإجازات والتقارير → تبويب <strong>الأطفال</strong> أو <strong>طاقم العمل</strong>.
</p>
