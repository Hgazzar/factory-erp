@props(['status'])

@php
    $classes = match ($status) {
        'completed' => 'clinic-status-completed',
        'cancelled' => 'clinic-status-cancelled',
        default => 'clinic-status-pending',
    };
    $label = \App\Models\Clinic\Appointment::statusLabels()[$status] ?? $status;
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">{{ $label }}</span>
