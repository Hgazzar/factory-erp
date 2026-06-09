@extends('layouts.fleet')

@section('title', 'صرف عهدة — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-4xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">صرف عهدة جديدة</h1>

    @if($errors->has('custody'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('custody') }}</div>
    @endif

    @if($products->isEmpty())
        <div class="fleet-card p-6 text-sm text-violet-800">
            أضف أصنافاً في <a href="{{ route('fleet.products.index') }}" class="font-semibold underline">الكتalog الخفيف</a> أولاً.
        </div>
    @else
        <form method="POST" action="{{ route('fleet.custody.store') }}" class="fleet-card p-6 space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_agent" /> المندوب</label>
                    <x-searchable-select
                        name="agent_id"
                        :options="collect($agents)->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name])->all()"
                        :selected="old('agent_id', '')"
                        empty-label="اختر المندوب"
                    />
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_issued_on" /> تاريخ الصرف</label>
                    <input type="date" name="issued_on" value="{{ old('issued_on', now()->toDateString()) }}" required class="w-full rounded-lg border-gray-300">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-2"><x-info field="fleet.custody_lines" /> بنود العهدة</label>
                <div class="space-y-3">
                    @for($i = 0; $i < 8; $i++)
                        <div class="grid sm:grid-cols-2 gap-3 items-end">
                            <div>
                                <x-searchable-select
                                    name="lines[{{ $i }}][product_id]"
                                    :options="$productOptions"
                                    :selected="old('lines.'.$i.'.product_id', '')"
                                    empty-label="اختر الصنف"
                                    empty-option
                                    label=""
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-violet-700 mb-1"><x-info field="fleet.custody_quantity" /> الكمية</label>
                                <input type="number" name="lines[{{ $i }}][quantity]" value="{{ old('lines.'.$i.'.quantity') }}" step="0.0001" min="0" class="w-full rounded-lg border-gray-300" placeholder="0">
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_notes" /> ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="fleet-btn fleet-btn-primary">حفظ مسودة</button>
                <a href="{{ route('fleet.custody.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
            </div>
        </form>
    @endif
</div>
@endsection
