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
    'percent' => null, // 0–100 drives spark fill; null = decorative fallback
])

@php
    $tones = [
        'primary' => [
            'hint' => '#0F766E',
            'spark' => '#14B8A6',
            'sparkSoft' => '#99F6E4',
            'sparkActive' => '#0F766E',
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
    $trend = in_array($trend, ['up', 'down', 'flat', 'none'], true) ? $trend : 'up';

    $hasPercent = $percent !== null && $percent !== '';
    $pct = $hasPercent ? max(0.0, min(100.0, (float) $percent)) : null;

    // Circumference for r=20 ≈ 125.66
    $ringCirc = 125.66;
    $ringDash = $pct !== null ? round(($pct / 100) * $ringCirc, 2) : 88.0;

    if ($pct !== null) {
        $peak = max(12.0, $pct);
        $barHeights = [];
        foreach ([0.35, 0.5, 0.42, 0.72, 0.58, 1.0, 0.78] as $factor) {
            $barHeights[] = (int) round(max(8, min(100, $peak * $factor)));
        }
        $lineY = static fn (float $share): float => 42 - (($peak * $share) / 100) * 34;
        $linePath = sprintf(
            'M2 %.1f C14 %.1f, 18 %.1f, 30 %.1f C42 %.1f, 46 %.1f, 58 %.1f C70 %.1f, 76 %.1f, 86 %.1f',
            $lineY(0.55),
            $lineY(0.55),
            $lineY(0.85),
            $lineY(0.72),
            $lineY(0.72),
            $lineY(0.4),
            $lineY(0.62),
            $lineY(0.62),
            $lineY(0.95),
            $lineY(1.0),
        );
    } else {
        $barHeights = [42, 62, 48, 78, 58, 92, 70];
        $linePath = 'M2 36 C14 34, 18 14, 30 16 C42 18, 46 38, 58 30 C70 22, 76 10, 86 12';
    }
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
                        <path d="{{ $linePath }}" stroke="var(--spark)" stroke-width="2.75" stroke-linecap="round" fill="none"/>
                        <path d="{{ $linePath }} V48 H2 Z" fill="var(--spark-soft)" opacity="0.4"/>
                    </svg>
                </div>
            @elseif($spark === 'ring')
                <div class="nursery-stat-spark nursery-stat-spark--ring" aria-hidden="true"
                     @if($pct !== null) title="{{ rtrim(rtrim(number_format($pct, 1, '.', ''), '0'), '.') }}%" @endif>
                    <svg viewBox="0 0 56 56">
                        <circle cx="28" cy="28" r="20" stroke="var(--spark-soft)" stroke-width="7" fill="none"/>
                        <circle cx="28" cy="28" r="20" stroke="var(--spark)" stroke-width="7" fill="none"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $ringDash }} {{ $ringCirc }}"
                                transform="rotate(-90 28 28)"/>
                    </svg>
                </div>
            @endif
        </div>
    </div>
</div>
