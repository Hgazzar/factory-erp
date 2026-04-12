@props([
    'field' => null,
    'hint' => null,
])

@php
    $resolvedHint = $hint;
    if (! $resolvedHint && $field) {
        $resolvedHint = data_get(config('hints'), $field);

        if (! $resolvedHint) {
            foreach ((array) config('hints', []) as $group) {
                if (is_array($group) && array_key_exists($field, $group)) {
                    $resolvedHint = $group[$field];
                    break;
                }
            }
        }
    }
@endphp

@if($resolvedHint)
    <span class="relative inline-flex items-center align-middle info-hint-trigger"
          role="button"
          tabindex="0"
          aria-label="معلومات"
          title="{{ $resolvedHint }}"
          data-hint="{{ $resolvedHint }}">
        <svg xmlns="http://www.w3.org/2000/svg"
             class="h-4 w-4 text-gray-400 hover:text-blue-500 transition-colors cursor-help"
             viewBox="0 0 20 20"
             fill="currentColor"
             aria-hidden="true"
             focusable="false">
            <path fill-rule="evenodd" d="M18 10A8 8 0 114.746 3.757a8 8 0 0113.254 6.243zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1.25 1.25 0 100 2.5A1.25 1.25 0 0010 5z" clip-rule="evenodd" />
        </svg>
    </span>
@endif
