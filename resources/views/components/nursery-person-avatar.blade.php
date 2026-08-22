@props([
    'name' => '',
    'src' => null,
    'size' => 'md', // sm|md|lg
])

@php
    $label = trim((string) $name);
    $initial = $label !== '' ? mb_strtoupper(mb_substr($label, 0, 1)) : '؟';
    $sizes = [
        'sm' => 'h-9 w-9 text-sm',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

@if(filled($src))
    <img src="{{ $src }}"
         alt="{{ $label !== '' ? $label : 'صورة' }}"
         {{ $attributes->merge(['class' => "nursery-person-avatar nursery-person-avatar--photo {$sizeClass} object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "nursery-person-avatar {$sizeClass}", 'aria-hidden' => 'true']) }}>{{ $initial }}</span>
@endif
