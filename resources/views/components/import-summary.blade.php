@props([
    'result' => [],
])

@php
    $failed = (int) ($result['failed'] ?? 0);
    $hasErrors = ! empty($result['errors'] ?? []);
    $isFailure = $failed > 0 || $hasErrors;
    $textClass = $isFailure ? 'text-red-800' : 'text-emerald-800';
    $borderClass = $isFailure ? 'border-red-200' : 'border-emerald-200';
    $bgClass = $isFailure ? 'bg-red-50' : 'bg-emerald-50';
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 rounded-xl border p-4 text-sm '.$borderClass.' '.$bgClass]) }}>
    <div class="mb-2 font-semibold {{ $textClass }}">ملخص الاستيراد</div>
    <div class="{{ $textClass }}">تمت الإضافة: {{ $result['created'] ?? 0 }} | تم التحديث: {{ $result['updated'] ?? 0 }} | فشل: {{ $failed }}</div>
    @if (isset($result['total_rows_processed']) || isset($result['successful_headers']))
        <div class="{{ $textClass }} mt-1">
            @if (isset($result['total_rows_processed']))
                عدد الصفوف المعالجة: {{ $result['total_rows_processed'] }}
            @endif
            @if (isset($result['successful_headers']))
                @if (isset($result['total_rows_processed']))
                    |
                @endif
                عدد الرؤوس الناجحة: {{ $result['successful_headers'] }}
            @endif
        </div>
    @endif
    @if ($hasErrors)
        <ul class="mt-2 max-h-40 list-disc space-y-1 overflow-auto pr-5 text-red-800">
            @foreach ($result['errors'] as $err)
                <li>السطر {{ $err['line'] ?? '-' }}: {{ $err['reason'] ?? 'خطأ غير معروف' }}</li>
            @endforeach
        </ul>
    @endif
</div>
