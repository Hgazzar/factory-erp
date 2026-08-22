@props([
    'title',
    'subtitle' => null,
    'info' => null,
    'chart' => 'donut', // donut|gauge|bars
    'centerLabel' => null,
    'centerValue' => null,
    'items' => [], // [['label' => '', 'value' => 0, 'color' => '#…'], ...]
    'href' => null,
    'linkLabel' => null,
])

@php
    $palette = ['#FB923C', '#10B981', '#0EA5E9', '#F43F5E', '#A855F7', '#F59E0B', '#64748B'];
    $normalized = [];
    foreach (array_values($items) as $i => $item) {
        $normalized[] = [
            'label' => (string) ($item['label'] ?? ''),
            'value' => (float) ($item['value'] ?? 0),
            'color' => (string) ($item['color'] ?? $palette[$i % count($palette)]),
        ];
    }
    $total = array_sum(array_column($normalized, 'value'));
    if ($total <= 0) {
        $total = 1;
        if ($normalized === []) {
            $normalized = [['label' => 'لا بيانات', 'value' => 1, 'color' => '#E2E8F0']];
        }
    }

    $donutSegments = [];
    $circumference = 2 * M_PI * 42;
    $offset = 0.0;
    foreach ($normalized as $seg) {
        $len = ($seg['value'] / $total) * $circumference;
        $donutSegments[] = [
            ...$seg,
            'dash' => $len,
            'gap' => max($circumference - $len, 0),
            'offset' => -$offset,
        ];
        $offset += $len;
    }

    // Semi-circle gauge: 180° arc, radius 48, path length ≈ π*r
    $gaugeR = 48;
    $gaugeLen = M_PI * $gaugeR;
    $gaugeSegments = [];
    $gOffset = 0.0;
    foreach ($normalized as $seg) {
        $len = ($seg['value'] / $total) * $gaugeLen;
        $gaugeSegments[] = [
            ...$seg,
            'dash' => $len,
            'gap' => max($gaugeLen - $len, 0),
            'offset' => -$gOffset,
        ];
        $gOffset += $len;
    }

    $barMax = max(1.0, max(array_column($normalized, 'value') ?: [1]));
    $displayCenter = $centerValue ?? (string) (int) round(array_sum(array_column($normalized, 'value')));
@endphp

<div {{ $attributes->merge(['class' => 'nursery-card nursery-chart-panel']) }}>
    <div class="nursery-chart-panel__inner">
        <div class="nursery-chart-panel__head">
            <div class="nursery-chart-panel__meta">
                @if(isset($icon))
                    <span class="nursery-chart-panel__icon">{{ $icon }}</span>
                @endif
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <h3 class="nursery-chart-panel__title">{{ $title }}</h3>
                        @if($info)
                            <x-info :field="$info" />
                        @endif
                    </div>
                    @if($subtitle)
                        <p class="nursery-chart-panel__sub">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @if($href && $linkLabel)
                <a href="{{ $href }}" class="nursery-chart-panel__link">{{ $linkLabel }}</a>
            @endif
        </div>

        {{-- في RTL: العنصر الأول يمين → البيانات يمين، الشارت شمال --}}
        <div class="nursery-chart-panel__split">
            <ul class="nursery-chart-panel__legend" role="list">
                @foreach($normalized as $item)
                    <li class="nursery-chart-panel__legend-item">
                        <span class="nursery-chart-panel__dot" style="background: {{ $item['color'] }}"></span>
                        <span class="nursery-chart-panel__legend-label">{{ $item['label'] }}</span>
                        <span class="nursery-chart-panel__legend-value tabular-nums">{{ is_float($item['value']) && floor($item['value']) != $item['value'] ? number_format($item['value'], 2) : (int) $item['value'] }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="nursery-chart-panel__chart" aria-hidden="true">
                @if($chart === 'gauge')
                    <div class="nursery-chart-gauge">
                        <svg viewBox="0 0 120 70" class="nursery-chart-gauge__svg">
                            <path d="M12 60 A48 48 0 0 1 108 60" fill="none" stroke="#F1F5F9" stroke-width="12" stroke-linecap="round"/>
                            @foreach($gaugeSegments as $seg)
                                <path d="M12 60 A48 48 0 0 1 108 60" fill="none"
                                      stroke="{{ $seg['color'] }}" stroke-width="12" stroke-linecap="butt"
                                      stroke-dasharray="{{ $seg['dash'] }} {{ $seg['gap'] }}"
                                      stroke-dashoffset="{{ $seg['offset'] }}"/>
                            @endforeach
                        </svg>
                        <div class="nursery-chart-gauge__center">
                            <p class="nursery-chart-gauge__value">{{ $displayCenter }}</p>
                            @if($centerLabel)
                                <p class="nursery-chart-gauge__label">{{ $centerLabel }}</p>
                            @endif
                        </div>
                    </div>
                @elseif($chart === 'bars')
                    <div class="nursery-chart-bars">
                        @foreach($normalized as $item)
                            <div class="nursery-chart-bars__col">
                                <span class="nursery-chart-bars__bar" style="height: {{ max(8, ($item['value'] / $barMax) * 100) }}%; background: {{ $item['color'] }}"></span>
                            </div>
                        @endforeach
                    </div>
                    @if($centerLabel)
                        <p class="nursery-chart-bars__caption">{{ $centerLabel }}: <strong>{{ $displayCenter }}</strong></p>
                    @endif
                @else
                    <div class="nursery-chart-donut">
                        <svg viewBox="0 0 100 100" class="nursery-chart-donut__svg">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="#F1F5F9" stroke-width="12"/>
                            @foreach($donutSegments as $seg)
                                <circle cx="50" cy="50" r="42" fill="none"
                                        stroke="{{ $seg['color'] }}" stroke-width="12"
                                        stroke-dasharray="{{ $seg['dash'] }} {{ $seg['gap'] }}"
                                        stroke-dashoffset="{{ $seg['offset'] }}"
                                        transform="rotate(-90 50 50)"/>
                            @endforeach
                        </svg>
                        <div class="nursery-chart-donut__center">
                            <p class="nursery-chart-donut__value">{{ $displayCenter }}</p>
                            @if($centerLabel)
                                <p class="nursery-chart-donut__label">{{ $centerLabel }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
