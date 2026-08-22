@extends(niche_shell_layout())

@section('title', 'عرض قيد - '.config('app.name'))

@section('breadcrumb')
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-indigo-600">الرئيسية</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.dashboard') }}" class="text-gray-500 hover:text-indigo-600">لوحة المحاسبة</a>
    <span class="mx-1 text-gray-400">›</span>
    <a href="{{ route('finance.journals.index') }}" class="text-gray-500 hover:text-indigo-600">القيود اليومية</a>
    <span class="mx-1 text-gray-400">›</span>
    <span class="text-indigo-900 font-semibold">عرض قيد {{ $entry->id }}</span>
@endsection

@section('content')
<div dir="rtl" class="mx-auto w-full max-w-5xl space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="inline-flex flex-wrap items-center gap-2 text-xl font-bold text-gray-900 md:text-2xl">
                <span>عرض قيد #{{ $entry->id }}</span>
                <x-info field="finance.journal_show_intro" />
            </h1>
            <p class="mt-1 text-sm text-gray-500">للقراءة فقط — استخدم «تعديل القيد» لتغيير البيانات.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('finance.journals.index') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">القائمة</a>
            <a href="{{ route('finance.journals.edit', $entry) }}" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">تعديل القيد</a>
        </div>
    </header>

    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-gray-800">
            <x-info field="finance.journal_show_detail_fields" />
            تفاصيل القيد
        </h2>
        <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div>
                <dt class="text-gray-500">تاريخ القيد</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $entry->date?->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">المرجع</dt>
                <dd class="mt-0.5 font-medium text-gray-900">{{ $entry->reference ?: '—' }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-gray-500">البيان</dt>
                <dd class="mt-0.5 text-gray-900">{{ $entry->description ?: '—' }}</dd>
            </div>
            @if(filled($entry->notes))
                <div class="md:col-span-2">
                    <dt class="text-gray-500">ملاحظات</dt>
                    <dd class="mt-0.5 whitespace-pre-wrap text-gray-900">{{ $entry->notes }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-gray-500">الإجمالي (مدين / دائن)</dt>
                <dd class="mt-0.5 font-mono font-semibold tabular-nums text-gray-900">{{ number_format((float) $entry->total, 2) }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-gray-800">
                <x-info field="finance.journal_show_lines" />
                بنود القيد
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">الحساب</th>
                        <th class="px-4 py-3 text-right font-medium">الوصف</th>
                        <th class="px-4 py-3 text-right font-medium">مركز التكلفة</th>
                        <th class="px-4 py-3 text-left font-medium">مدين</th>
                        <th class="px-4 py-3 text-left font-medium">دائن</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($entry->items as $line)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 text-right text-gray-900">
                                @if($line->account)
                                    <span class="font-mono text-xs text-gray-600">{{ $line->account->code }}</span>
                                    <span class="mr-1">— {{ $line->account->name_ar ?? $line->account->name_en ?? '—' }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="max-w-[200px] truncate px-4 py-3 text-right text-gray-700" title="{{ $line->description }}">{{ $line->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ $line->cost_center ?: '—' }}</td>
                            <td class="px-4 py-3 text-left font-mono tabular-nums text-gray-900">{{ $line->debit > 0 ? number_format((float) $line->debit, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-left font-mono tabular-nums text-gray-900">{{ $line->credit > 0 ? number_format((float) $line->credit, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($entry->attachments->isNotEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 inline-flex flex-wrap items-center gap-1 text-sm font-semibold text-gray-800">
                <x-info field="finance.journal_show_attachments" />
                المرفقات
            </h2>
            <ul class="space-y-2 text-sm">
                @foreach($entry->attachments as $att)
                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2">
                        <span class="text-gray-800">{{ $att->file_name ?? basename($att->file_path ?? '') }}</span>
                        @if($att->file_path)
                            <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">فتح / تنزيل</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
