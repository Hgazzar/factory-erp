@props([
    'menuId',
])

<div class="relative inline-flex items-center justify-center">
    <button type="button"
            class="erp-actions-trigger inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
            data-actions-menu="{{ $menuId }}"
            aria-haspopup="menu"
            aria-expanded="false"
            title="المزيد من الإجراءات"
            aria-label="المزيد من الإجراءات">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
        </svg>
    </button>
    <div id="{{ $menuId }}"
         class="erp-actions-menu hidden min-w-[13rem] rounded-xl border border-gray-200/90 bg-white py-2 shadow-2xl ring-1 ring-black/5"
         style="list-style: none;"
         role="menu"
         dir="rtl">
        {{ $slot }}
    </div>
</div>
