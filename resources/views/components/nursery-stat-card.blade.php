@props([
    'title',
    'value',
    'hint' => null,
    'info' => null,
    'tone' => 'primary', // primary|success|warning|info|muted|danger
    'href' => null,
    'linkLabel' => null,
    'spark' => 'bars', // bars|line|ring|none
    'trend' => 'up', // up|down|flat|none
])

@php
    $tones = [
        'primary' => [
            'hint' => '#EA580C',
            'spark' => '#FB923C',
            'sparkSoft' => '#FED7AA',
            'sparkActive' => '#EA580C',
        ],
        'success' => [
            'hint' => '#059669',
            'spark' => '#34D399',
            'sparkSoft' => '#A7F3D0',
            'sparkActive' => '#059669',
        ],
        'warning' => [
            'hint' => '#D97706',
            'spark' => '#FBBF24',
            'sparkSoft' => '#FDE68A',
            'sparkActive' => '#D97706',
        ],
        'info' => [
            'hint' => '#0284C7',
            'spark' => '#38BDF8',
            'sparkSoft' => '#BAE6FD',
            'sparkActive' => '#0284C7',
        ],
        'muted' => [
            'hint' => '#64748B',
            'spark' => '#94A3B8',
            'sparkSoft' => '#E2E8F0',
            'sparkActive' => '#64748B',
        ],
        'danger' => [
            'hint' => '#E11D48',
            'spark' => '#FB7185',
            'sparkSoft' => '#FECDD3',
            'sparkActive' => '#E11D48',
        ],
    ];
    $t = $tones[$tone] ?? $tones['primary'];
    $barHeights = [42, 62, 48, 78, 58, 92, 70];
    $trend = in_array($trend, ['up', 'down', 'flat', 'none'], true) ? $trend : 'up';
@endphp

<div {{ $attributes->merge(['class' => 'nursery-card nursery-admina-stat']) }}
     style="--spark: {{ $t['spark'] }}; --spark-soft: {{ $t['sparkSoft'] }}; --spark-active: {{ $t['sparkActive'] }}; --hint: {{ $t['hint'] }};">
    <div class="nursery-admina-stat__inner">
        <div class="nursery-admina-stat__head">
            <div class="min-w-0 flex items-center gap-1.5">
                <p class="nursery-admina-stat__title">{{ $title }}</p>
                @if($info)
                    <x-info :field="$info" />
                @endif
            </div>
            @if(filled($href))
                <a href="{{ $href }}" class="nursery-admina-stat__menu" title="{{ $linkLabel ?: 'انتقال' }}" aria-label="{{ $linkLabel ?: 'انتقال' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                    </svg>
                </a>
            @endif
        </div>

        {{-- RTL: العنصر الأول يمين = البيانات، الثاني شمال = الرسمة --}}
        <div class="nursery-admina-stat__body">
            <div class="nursery-admina-stat__data">
                <p class="nursery-admina-stat__value">{{ $value }}</p>
                @if($hint)
                    <p class="nursery-admina-stat__hint">
                        @if($trend === 'up')
                            <span class="nursery-admina-stat__arrow" aria-hidden="true">↑</span>
                        @elseif($trend === 'down')
                            <span class="nursery-admina-stat__arrow" aria-hidden="true">↓</span>
                        @elseif($trend === 'flat')
                            <span class="nursery-admina-stat__arrow" aria-hidden="true">→</span>
                        @endif
                        <span>{{ $hint }}</span>
                    </p>
                @endif
                @if(filled($href) && filled($linkLabel))
                    <a href="{{ $href }}" class="nursery-admina-stat__link">{{ $linkLabel }}</a>
                @endif
            </div>

            @if($spark === 'bars')
                <div class="nursery-stat-spark nursery-stat-spark--bars" aria-hidden="true">
                    @foreach($barHeights as $i => $h)
                        <span class="{{ $i === count($barHeights) - 2 ? 'is-active' : '' }}" style="height: {{ $h }}%"></span>
                    @endforeach
                </div>
            @elseif($spark === 'line')
                <div class="nursery-stat-spark nursery-stat-spark--line" aria-hidden="true">
                    <svg viewBox="0 0 88 48" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                        <path d="M2 36 C14 34, 18 14, 30 16 C42 18, 46 38, 58 30 C70 22, 76 10, 86 12" stroke="var(--spark)" stroke-width="2.75" stroke-linecap="round" fill="none"/>
                        <path d="M2 36 C14 34, 18 14, 30 16 C42 18, 46 38, 58 30 C70 22, 76 10, 86 12 V48 H2 Z" fill="var(--spark-soft)" opacity="0.4"/>
                    </svg>
                </div>
            @elseif($spark === 'ring')
                <div class="nursery-stat-spark nursery-stat-spark--ring" aria-hidden="true">
                    <svg viewBox="0 0 56 56">
                        <circle cx="28" cy="28" r="20" stroke="var(--spark-soft)" stroke-width="7" fill="none"/>
                        <circle cx="28" cy="28" r="20" stroke="var(--spark)" stroke-width="7" fill="none"
                                stroke-linecap="round" stroke-dasharray="88 126" transform="rotate(-90 28 28)"/>
                    </svg>
                </div>
            @endif
        </div>
    </div>
</div>
