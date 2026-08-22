@props([
    'name',
    'id',
    'options' => [],
    'value' => null,
    'required' => false,
    'error' => false,
    'emptyOption' => true,
    'emptyLabel' => '',
    'placeholder' => 'ابحث بالاسم أو الكود...',
    'inModal' => false,
    /** لو true: قائمة منسدلة position:fixed لتفادي القص داخل overflow أو نافذة منبثقة */
    'fixedPanel' => false,
    /** عند true: لا يُصدَر input مخفي؛ ضع حقلاً مخفياً في الأب مع x-model (نماذج Alpine متداخلة) */
    'omitHidden' => false,
    /** عند false: إخفاء حقل البحث داخل اللوحة (قوائم قصيرة مثل الحالة) */
    'searchable' => true,
])

@php
    $selectedRaw = old($name, $value);
    $selectedStr = $selectedRaw === null || $selectedRaw === '' ? '' : (string) $selectedRaw;
    $id = filled($id ?? null)
        ? (string) $id
        : 'erp-ss-'.preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $name).'-'.substr(uniqid('', true), -8);
    $wireAttrs = $attributes->filter(fn ($v, $k) => is_string($k) && str_starts_with($k, 'wire:model'));
    $outerAttrs = $attributes->except(array_keys($wireAttrs->getAttributes()));
    if ($omitHidden) {
        $outerAttrs = $outerAttrs->merge($wireAttrs->getAttributes());
    }
    $normalized = [];
    foreach ($options as $row) {
        if (is_array($row)) {
            $normalized[] = [
                'v' => (string) ($row['value'] ?? ''),
                'l' => (string) ($row['label'] ?? ''),
            ];
        } elseif (is_object($row)) {
            $normalized[] = [
                'v' => (string) ($row->value ?? ''),
                'l' => (string) ($row->label ?? ''),
            ];
        }
    }
    if ($emptyOption) {
        array_unshift($normalized, [
            'v' => '',
            'l' => $emptyLabel !== '' ? $emptyLabel : '—',
        ]);
    }
    $panelZ = $inModal ? 'z-[1060]' : 'z-40';
    $useFixed = $fixedPanel || $inModal;
    $panelPositionClass = $useFixed ? 'fixed' : 'absolute mt-1';
    $borderClass = $error ? 'border-red-500 ring-1 ring-red-200' : 'border-gray-200';
@endphp

<div
    {{ $outerAttrs->class(['erp-searchable-select', 'relative', 'w-full']) }}
    x-data="{
        open: false,
        q: '',
        items: @js($normalized),
        selected: @js($selectedStr),
        placeholder: @js($placeholder),
        useFixed: @js($useFixed),
        panelTop: 0,
        panelLeft: 0,
        panelWidth: 0,
        positionPanel() {
            if (!this.useFixed) return;
            const btn = this.$refs.triggerBtn;
            const panel = this.$refs.dropdownPanel;
            if (!btn || !panel) return;
            const r = btn.getBoundingClientRect();
            const width = Math.max(r.width, 160);
            const maxLeft = Math.max(8, window.innerWidth - width - 8);
            this.panelWidth = width;
            this.panelTop = r.bottom + 4;
            this.panelLeft = Math.min(Math.max(8, r.left), maxLeft);
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.positionPanel();
                    if (this.$refs.q) { this.$refs.q.focus(); }
                });
            }
        },
        close() {
            this.open = false;
            this.q = '';
        },
        pick(v) {
            this.selected = v;
            this.close();
            const detail = { name: @js($name), value: v };
            this.$dispatch('searchable-select-change', detail);
            this.$dispatch('custom-select-change', detail);
            this.$nextTick(() => {
                const h = this.$refs.hiddenInput;
                if (h) {
                    h.dispatchEvent(new Event('input', { bubbles: true }));
                    h.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        },
        clearSel() {
            this.selected = '';
            this.q = '';
            const detail = { name: @js($name), value: '' };
            this.$dispatch('searchable-select-change', detail);
            this.$dispatch('custom-select-change', detail);
            this.$nextTick(() => {
                const h = this.$refs.hiddenInput;
                if (h) {
                    h.dispatchEvent(new Event('input', { bubbles: true }));
                    h.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        },
        display() {
            const it = this.items.find(i => String(i.v) === String(this.selected));
            return it ? it.l : @js($emptyLabel !== '' ? $emptyLabel : '—');
        },
        get filtered() {
            const qq = (this.q || '').trim().toLowerCase();
            if (!qq) {
                return this.items;
            }
            return this.items.filter(i => {
                if (i.v === '') {
                    return true;
                }
                const lab = (i.l || '').toLowerCase();
                const val = (i.v || '').toLowerCase();
                return lab.includes(qq) || val.includes(qq);
            });
        },
    }"
    @if($useFixed)
        @resize.window="if (open) positionPanel()"
        @scroll.window.passive="if (open) positionPanel()"
    @endif
    @if(! $omitHidden)
        x-modelable="selected"
    @endif
    @erp-sync-searchable.window="if ($event.detail && String($event.detail.id) === @js($id)) { selected = $event.detail.value != null && $event.detail.value !== '' ? String($event.detail.value) : ''; }"
    @click.outside="close()"
>
    @unless($omitHidden)
        <input
            type="hidden"
            name="{{ $name }}"
            id="{{ $id }}"
            x-ref="hiddenInput"
            @if($required) required @endif
            x-bind:value="selected"
            @if(! $omitHidden)
                {{ $wireAttrs }}
            @endif
        >
    @endunless
    <button
        type="button"
        id="{{ $id }}-trigger"
        x-ref="triggerBtn"
        @click="toggle()"
        @keydown.escape.prevent.stop="close()"
        :aria-expanded="open"
        aria-haspopup="listbox"
        :aria-controls="'{{ $id }}-listbox'"
        class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border bg-white px-3 text-right text-sm text-gray-900 shadow-sm focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-0 {{ $borderClass }}"
    >
        <span class="min-w-0 flex-1 truncate font-normal" x-text="display()"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-ref="dropdownPanel"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="{{ $panelZ }} {{ $panelPositionClass }} @if(! $useFixed) w-full @endif flex max-h-[min(18rem,50vh)] flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg ring-1 ring-black/5"
        :style="useFixed ? ('top:' + panelTop + 'px;left:' + panelLeft + 'px;width:' + Math.max(panelWidth, 160) + 'px;max-width:calc(100vw - 1rem);max-height:min(18rem,50vh)') : ''"
        id="{{ $id }}-listbox"
        role="listbox"
        @click.stop
        @keydown.escape.prevent.stop="close()"
    >
        @if($searchable)
            <div class="shrink-0 border-b border-gray-100 px-2 pb-1.5 pt-1">
                <input
                    type="search"
                    x-ref="q"
                    x-model="q"
                    :placeholder="placeholder"
                    autocomplete="off"
                    class="h-9 w-full rounded-md border border-gray-200 bg-gray-50 px-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    dir="rtl"
                >
            </div>
        @endif
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <ul class="py-0.5 text-sm" role="presentation">
                <template x-for="(row, idx) in filtered" :key="row.v + '-' + idx + '-' + row.l">
                    <li role="option" :aria-selected="String(selected) === String(row.v)">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-right hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
                            :class="String(selected) === String(row.v) ? 'bg-blue-50 font-semibold text-blue-900' : 'text-gray-800'"
                            @click="pick(row.v)"
                            x-text="row.l"
                        ></button>
                    </li>
                </template>
            </ul>
        </div>
        @if($emptyOption)
            <div class="shrink-0 flex justify-end border-t border-gray-100 px-2 py-1" x-show="selected !== '' && selected !== null">
                <button type="button" class="text-xs font-medium text-gray-600 hover:text-blue-700" @click="clearSel()">مسح الاختيار</button>
            </div>
        @endif
    </div>
</div>
