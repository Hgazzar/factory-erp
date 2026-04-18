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
    'allowDelete' => false,
])

@php
    $existingRows = $existing instanceof \Illuminate\Support\Collection ? $existing : collect($existing);
@endphp

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
                                        <td class="text-center">
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0"
                                                data-url="{{ route('attachments.destroy', $att) }}"
                                                onclick="(async (btn) => { if (!confirm('حذف هذا المرفق؟')) return; const u = btn.getAttribute('data-url'); const t = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''; const r = await fetch(u, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': t, 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html,application/json' }, credentials: 'same-origin' }); if (r.ok || r.status === 302) location.reload(); else alert('تعذر حذف المرفق'); })(this)">حذف</button>
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
                                        <td class="px-2 py-2 text-center">
                                            <button type="button"
                                                class="text-xs font-medium text-red-600 hover:text-red-800"
                                                data-url="{{ route('attachments.destroy', $att) }}"
                                                onclick="(async (btn) => { if (!confirm('حذف هذا المرفق؟')) return; const u = btn.getAttribute('data-url'); const t = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''; const r = await fetch(u, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': t, 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html,application/json' }, credentials: 'same-origin' }); if (r.ok || r.status === 302) location.reload(); else alert('تعذر حذف المرفق'); })(this)">حذف</button>
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
