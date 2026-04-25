{{--
    قائمة منسدلة قابلة للبحث (Alpine) — غلاف حول searchable-select بنفس السلوك.
    - selected أو value: القيمة الافتراضية (يُدمج مع old(name)).
    - wire:model* يُمرَّر إلى الحقل المخفي (أو للجذر عند omitHidden).
    - searchable=false: إخفاء حقل البحث (قوائم قصيرة).
    - يُصدَر أيضاً الحدث custom-select-change (و searchable-select-change للتوافق).
--}}
@props([
    'name',
    'id' => null,
    'options' => [],
    'selected' => null,
    'value' => null,
    'placeholder' => 'ابحث بالاسم أو الكود...',
    'required' => false,
    'error' => false,
    'emptyOption' => true,
    'emptyLabel' => null,
    'inModal' => false,
    'fixedPanel' => false,
    'omitHidden' => false,
    'searchable' => true,
])

@php
    $id = $id ?? 'cs_'.preg_replace('/[^\w\-]/', '_', (string) $name);
    $resolvedValue = $selected ?? $value;
@endphp

<x-searchable-select
    :name="$name"
    :id="$id"
    :options="$options"
    :value="$resolvedValue"
    :required="$required"
    :error="$error"
    :empty-option="(bool) $emptyOption"
    :empty-label="$emptyLabel ?? '— اختر —'"
    :placeholder="$placeholder"
    :in-modal="$inModal"
    :fixed-panel="$fixedPanel"
    :omit-hidden="$omitHidden"
    :searchable="$searchable"
    {{ $attributes->class(['custom-select']) }}
/>
