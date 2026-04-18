@props([
    'existing' => [],
    'hintField' => null,
    'title' => 'المرفقات',
    'inputName' => 'attachments[]',
    'accept' => '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv',
    'theme' => 'tailwind',
    'helpText' => null,
    'showExisting' => true,
    'uploadable' => true,
    'allowDelete' => true,
])

@php
    $existingRows = $existing instanceof \Illuminate\Support\Collection ? $existing : collect($existing);
@endphp

<script>
    if (typeof window.__erpDeleteAttachment !== 'function') {
        window.__erpDeleteAttachment = function (ev, el) {
            if (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                if (typeof ev.stopImmediatePropagation === 'function') {
                    ev.stopImmediatePropagation();
                }
            }
            if (! el || ! el.getAttribute('data-url')) {
                return false;
            }
            if (! confirm('حذف هذا المرفق؟')) {
                return false;
            }
            const url = el.getAttribute('data-url');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const runFetch = function () {
                const body = new FormData();
                body.append('_method', 'DELETE');
                body.append('_token', token);
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'text/html,application/json',
                    },
                    body: body,
                    credentials: 'same-origin',
                }).then(function (r) {
                    if (r.ok || r.status === 302) {
                        window.location.reload();
                    } else {
                        alert('تعذر حذف المرفق');
                    }
                }).catch(function () {
                    alert('تعذر حذف المرفق');
                });
            };
            setTimeout(runFetch, 0);

            return false;
        };
    }
</script>

@if($theme === 'bootstrap')
    <div class="space-y-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if($hintField)
                <x-info :field="$hintField" />
            @endif
            <span class="form-label fw-semibold mb-0">{{ $title }}</span>
        </div>
        @if($helpText)
            <p class="text-muted small mb-0">{{ $helpText }}</p>
        @endif
        @if($showExisting)
            @if($existingRows->isEmpty())
                <p class="text-muted small mb-0">لا توجد مرفقات مسجّلة بعد.</p>
            @else
                <div class="table-responsive rounded-lg border border-secondary-subtle">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="text-center" style="width:4.5rem"><span class="inline-flex align-items-center gap-1"><x-info field="attachments.col_preview" /> معاينة</span></th>
                                <th scope="col" class="text-end"><span class="inline-flex align-items-center gap-1"><x-info field="attachments.col_file_name" /> اسم الملف</span></th>
                                <th scope="col" class="text-end"><span class="inline-flex align-items-center gap-1"><x-info field="attachments.col_file_type" /> النوع</span></th>
                                <th scope="col" class="text-end"><span class="inline-flex align-items-center gap-1"><x-info field="attachments.col_file_size" /> الحجم</span></th>
                                <th scope="col" class="text-center"><span class="inline-flex align-items-center gap-1"><x-info field="attachments.col_link" /> رابط</span></th>
                                @if($allowDelete)
                                    <th scope="col" class="text-center" style="width:5rem"><span class="inline-flex align-items-center gap-1"><x-info field="attachments.col_delete" /> حذف</span></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($existingRows as $att)
                                @php
                                    $url = asset('storage/'.ltrim($att->file_path ?? '', '/'));
                                    $isImage = str_starts_with((string) ($att->file_type ?? ''), 'image/');
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">
                                        @if($isImage)
                                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="d-inline-block">
                                                <img src="{{ $url }}" alt="" class="rounded border" style="width:2.5rem;height:2.5rem;object-fit:cover">
                                            </a>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end small">{{ $att->file_name }}</td>
                                    <td class="text-end small text-muted">{{ $att->file_type ?? '—' }}</td>
                                    <td class="text-end small text-muted tabular-nums">{{ $att->file_size ? number_format((int) $att->file_size / 1024, 1).' ك.ب' : '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="small">فتح</a>
                                    </td>
                                    @if($allowDelete)
                                        <td class="text-center align-middle">
                                            <a href="javascript:void(0)"
                                                role="button"
                                                tabindex="0"
                                                @@click.prevent.stop="$event.preventDefault(); $event.stopPropagation()"
                                                class="btn btn-sm btn-outline-danger border-0 p-1.5 rounded text-danger hover:bg-red-50 text-decoration-none d-inline-flex align-items-center justify-content-center"
                                                title="حذف المرفق"
                                                aria-label="حذف المرفق"
                                                data-url="{{ route('attachments.destroy', $att) }}"
                                                onclick="event.preventDefault(); event.stopPropagation(); if (typeof event.stopImmediatePropagation === 'function') { event.stopImmediatePropagation(); } window.__erpDeleteAttachment(event, this); return false;">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
        @if($uploadable)
            <div>
                <label class="form-label d-flex align-items-center gap-1 mb-1">
                    @if($hintField)<x-info :field="$hintField" />@endif
                    <span>إضافة ملفات</span>
                </label>
                <input type="file" name="{{ $inputName }}" multiple accept="{{ $accept }}" class="form-control form-control-sm">
                @error('attachments')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        @endif
    </div>
@else
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
            @if($hintField)
                <x-info :field="$hintField" />
            @endif
        </div>
        @if($helpText)
            <p class="text-sm text-gray-600">{{ $helpText }}</p>
        @endif
        @if($showExisting)
            @if($existingRows->isEmpty())
                <p class="text-sm text-gray-500">لا توجد مرفقات مسجّلة بعد.</p>
            @else
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full border-collapse text-sm text-gray-800">
                        <thead class="border-b border-gray-200 bg-gray-50 text-gray-700">
                            <tr>
                                <th class="w-16 px-2 py-2 text-center text-xs font-medium">
                                    <span class="inline-flex items-center gap-1"><x-info field="attachments.col_preview" /> معاينة</span>
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    <span class="inline-flex items-center gap-1"><x-info field="attachments.col_file_name" /> اسم الملف</span>
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    <span class="inline-flex items-center gap-1"><x-info field="attachments.col_file_type" /> النوع</span>
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    <span class="inline-flex items-center gap-1"><x-info field="attachments.col_file_size" /> الحجم</span>
                                </th>
                                <th class="px-3 py-2 text-center font-medium">
                                    <span class="inline-flex items-center gap-1"><x-info field="attachments.col_link" /> رابط</span>
                                </th>
                                @if($allowDelete)
                                    <th class="w-20 px-2 py-2 text-center text-xs font-medium">
                                        <span class="inline-flex items-center gap-1"><x-info field="attachments.col_delete" /> حذف</span>
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($existingRows as $att)
                                @php
                                    $url = asset('storage/'.ltrim($att->file_path ?? '', '/'));
                                    $isImage = str_starts_with((string) ($att->file_type ?? ''), 'image/');
                                @endphp
                                <tr class="border-b border-gray-100 bg-white">
                                    <td class="px-2 py-2 text-center align-middle">
                                        @if($isImage)
                                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-block">
                                                <img src="{{ $url }}" alt="" class="h-10 w-10 rounded-md border border-gray-200 object-cover" loading="lazy">
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right break-all">{{ $att->file_name }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs">{{ $att->file_type ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right text-gray-500 text-xs tabular-nums">{{ $att->file_size ? number_format((int) $att->file_size / 1024, 1).' ك.ب' : '—' }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">فتح</a>
                                    </td>
                                    @if($allowDelete)
                                        <td class="px-2 py-2 text-center align-middle">
                                            <a href="javascript:void(0)"
                                                role="button"
                                                tabindex="0"
                                                @@click.prevent.stop="$event.preventDefault(); $event.stopPropagation()"
                                                class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white p-2 text-red-600 shadow-sm transition hover:bg-red-50 hover:border-red-300 no-underline"
                                                title="حذف المرفق"
                                                aria-label="حذف المرفق"
                                                data-url="{{ route('attachments.destroy', $att) }}"
                                                onclick="event.preventDefault(); event.stopPropagation(); if (typeof event.stopImmediatePropagation === 'function') { event.stopImmediatePropagation(); } window.__erpDeleteAttachment(event, this); return false;">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
        @if($uploadable)
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-gray-700">
                    @if($hintField)<x-info :field="$hintField" />@endif
                    <span>إضافة ملفات</span>
                </label>
                <input type="file" name="{{ $inputName }}" multiple accept="{{ $accept }}" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm file:me-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:text-gray-700">
                @error('attachments')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('attachments.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endif
    </div>
@endif
