@extends('layouts.nursery')

@section('title', 'التقويم')

@section('content')
<div class="w-full space-y-4" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-teal-950">التقويم</h1>
            <p class="text-sm text-teal-800/80"><x-info field="nursery.nav_calendar" /> جدولة الدروس والأنشطة والإعلانات</p>
        </div>
        @if($canManage)
            <a href="{{ route('nursery.calendar.create') }}" class="nursery-btn nursery-btn-primary">+ إضافة للتقويم</a>
        @endif
    </div>

    @if(session('success'))
        <div class="nursery-card px-4 py-3 text-sm text-emerald-800 bg-emerald-50 border-emerald-200">{{ session('success') }}</div>
    @endif

    <form method="get" id="nurseryCalendarFilters" class="nursery-card p-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <div>
            <label class="block text-sm font-semibold text-teal-950 mb-1">
                الفصل
                <x-info field="nursery.calendar_filter_classroom" />
            </label>
            <x-custom-select name="classroom_id" :options="$classroomOptions"
                :value="(string) ($classroomFilter ?? '')" :searchable="true" />
        </div>
        <div>
            <label class="block text-sm font-semibold text-teal-950 mb-1">
                فرز بحسب
                <x-info field="nursery.calendar_filter_type" />
            </label>
            <x-custom-select name="type" :options="$typeOptions" :value="$typeFilter" :searchable="false" />
        </div>
        <input type="hidden" name="view" id="calendar_view_input" value="{{ $initialView }}">
        <input type="hidden" name="from" id="calendar_from_input" value="{{ $initialDate }}">
        <button type="submit" class="nursery-btn nursery-btn-soft sm:col-span-2 lg:col-span-4">تطبيق</button>
    </form>

    <section class="nursery-card p-3 sm:p-4">
        <div id="nurseryCalendar" class="min-h-[32rem]"></div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('nurseryCalendar');
    if (!el || typeof FullCalendar === 'undefined') return;

    var filterForm = document.getElementById('nurseryCalendarFilters');
    var viewInput = document.getElementById('calendar_view_input');
    var fromInput = document.getElementById('calendar_from_input');

    var calendar = new FullCalendar.Calendar(el, {
        locale: 'ar',
        direction: 'rtl',
        initialView: @json($initialView),
        initialDate: @json($initialDate),
        height: 'auto',
        slotMinTime: '06:00:00',
        slotMaxTime: '20:00:00',
        allDaySlot: false,
        headerToolbar: {
            right: 'prev,next today',
            center: 'title',
            left: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'اليوم',
            month: 'شهر',
            week: 'أسبوع',
            day: 'يوم'
        },
        events: {
            url: @json(route('nursery.calendar.events')),
            extraParams: function () {
                var classroom = filterForm.querySelector('[name="classroom_id"]');
                var type = filterForm.querySelector('[name="type"]');
                return {
                    classroom_id: classroom ? classroom.value : '',
                    type: type ? type.value : ''
                };
            }
        },
        datesSet: function (info) {
            if (viewInput) viewInput.value = info.view.type;
            if (fromInput) fromInput.value = info.startStr.slice(0, 10);
        },
        eventClick: function (info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        }
    });

    calendar.render();

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        calendar.refetchEvents();
    });
});
</script>
@endpush
