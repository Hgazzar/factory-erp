@extends('layouts.nursery-portal')

@section('title', 'التقويم')

@section('content')
<div class="space-y-4">
    <div class="np-card">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-extrabold text-teal-950">التقويم</h2>
                <p class="text-sm text-teal-800/75 mt-1">
                    {{ $grid['weekStart']->translatedFormat('j M') }} — {{ $grid['weekEnd']->translatedFormat('j M Y') }}
                    <x-info field="nursery.portal_calendar_intro" />
                </p>
            </div>
            <div class="flex gap-2 text-sm">
                <a href="{{ $prevWeekUrl }}" class="np-btn np-btn-soft !w-auto px-3 py-1">←</a>
                <a href="{{ $nextWeekUrl }}" class="np-btn np-btn-soft !w-auto px-3 py-1">→</a>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        @foreach($grid['days'] as $day)
            <div @class(['np-card', 'ring-2 ring-teal-300' => $day['is_today']])>
                <h3 class="font-bold text-teal-950 mb-2">{{ $day['label'] }}</h3>
                @if($day['events'] === [])
                    <p class="text-sm text-teal-700/60">لا أحداث</p>
                @else
                    <ul class="space-y-2">
                        @foreach($day['events'] as $event)
                            <li class="rounded-lg border border-teal-100 p-2.5" style="border-right: 4px solid {{ $event['color'] }}">
                                <div class="font-semibold text-teal-950 text-sm">{{ $event['title'] }}</div>
                                <div class="text-xs text-teal-800/75 mt-0.5">{{ $event['type_label'] }} · {{ $event['time'] }}</div>
                                @if(!empty($event['notes']))
                                    <p class="text-xs text-teal-700/80 mt-1">{{ $event['notes'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('bottom_nav')
    @include('nursery.partials.portal-nav')
@endsection
