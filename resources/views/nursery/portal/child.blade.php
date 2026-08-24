@extends('layouts.nursery-portal')

@section('title', $child->name)

@section('content')
<div class="space-y-4">
    <a href="{{ route('nursery.portal.home', ['tenant_slug' => $tenantSlug]) }}"
       class="text-sm font-semibold text-teal-700 hover:text-teal-900">← أطفالي</a>

    <div class="np-card">
        <h2 class="text-xl font-extrabold text-teal-950">{{ $child->name }}</h2>
        <p class="text-sm text-teal-800/75 mt-1">
            {{ $child->activeEnrollment?->classroom?->name ?? 'غير معين لفصل' }}
            @if($child->code)<span class="mx-1">·</span><span dir="ltr">{{ $child->code }}</span>@endif
        </p>
    </div>

    <section class="np-card space-y-2">
        <h3 class="font-bold text-teal-950">
            حالة اليوم
            <x-info field="nursery.portal_child_today_status" />
        </h3>
        <p class="text-lg font-extrabold
            @class([
                'text-emerald-600' => in_array($todayStatus, ['present', 'checked_out'], true),
                'text-amber-600' => $todayStatus === 'late',
                'text-red-600' => $todayStatus === 'absent',
                'text-teal-700' => $todayStatus === 'no_record',
            ])">
            {{ $todayStatusLabel }}
        </p>
        @if($todayLog?->checked_in_at)
            <p class="text-sm text-teal-800/80">وقت الحضور: {{ $todayLog->checked_in_at->format('H:i') }}</p>
        @endif
        @if($todayLog?->checked_out_at)
            <p class="text-sm text-teal-800/80">وقت الانصراف: {{ $todayLog->checked_out_at->format('H:i') }}</p>
        @endif
    </section>

    <section class="np-card space-y-3">
        <h3 class="font-bold text-teal-950">
            يوم الطفل
            <x-info field="nursery.portal_child_daily" />
        </h3>
        @forelse($dailySummary ?? [] as $group)
            <div class="text-sm">
                <p class="font-semibold text-teal-950">{{ $group['label'] }}</p>
                <ul class="mt-1 space-y-1 text-teal-800/90">
                    @foreach($group['lines'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-sm text-teal-700/70">لا يوجد ملخص لليوم بعد.</p>
        @endforelse
    </section>

    <section class="np-card space-y-3">
        <h3 class="font-bold text-teal-950">المعلومات الصحية</h3>
        <dl class="space-y-2 text-sm">
            <div><dt class="text-teal-800/70 inline">الحساسية: </dt><dd class="inline font-medium">{{ $child->allergies ?: '—' }}</dd></div>
            <div><dt class="text-teal-800/70 inline">الأمراض: </dt><dd class="inline font-medium">{{ $child->diseases ?: '—' }}</dd></div>
            <div><dt class="text-teal-800/70 inline">ملاحظات: </dt><dd class="inline font-medium">{{ $child->health_notes ?: '—' }}</dd></div>
        </dl>
    </section>

    <section class="np-card space-y-3">
        <h3 class="font-bold text-teal-950">
            الأدوية
            <x-info field="nursery.portal_medications_readonly" />
        </h3>
        <p class="text-xs text-teal-700/70">عرض للاطلاع فقط — لا يمكن التعديل من البوابة.</p>
        @include('nursery.partials.medications-readonly-table', ['medications' => $child->medications])
    </section>
</div>
@endsection

@section('bottom_nav')
    @include('nursery.partials.portal-nav')
@endsection
