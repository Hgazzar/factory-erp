@extends('layouts.fleet')

@section('title', 'مرتجع عهدة — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-4xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">تسجيل مرتجع عهدة</h1>

    @if($errors->has('custody_return'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('custody_return') }}</div>
    @endif

    <form method="POST" action="{{ route('fleet.custody.returns.store') }}" class="fleet-card p-6 space-y-4">
        @csrf
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_agent" /> المندوب</label>
                <x-searchable-select
                    name="agent_id"
                    :options="$agentOptions"
                    :selected="$selectedAgentId"
                    empty-label="اختر المندوب"
                />
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.custody_returned_on" /> تاريخ المرتجع</label>
                <input type="date" name="returned_on" value="{{ old('returned_on', now()->toDateString()) }}" required class="w-full rounded-lg border-gray-300">
            </div>
        </div>

        @if($agentBalanceLines->isNotEmpty())
            <div class="rounded-lg border border-violet-100 bg-violet-50/50 p-4 text-sm">
                <p class="font-semibold text-violet-900 mb-2"><x-info field="fleet.custody_balance" /> رصيد المندوب الحالي</p>
                <ul class="space-y-1 text-violet-800">
                    @foreach($agentBalanceLines as $line)
                        <li>{{ $line->product_name }} — {{ number_format($line->quantity, 2) }}</li>
                    @endforeach
                </ul>
            </div>
        @elseif($selectedAgentId !== '')
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">لا يوجد رصيد عهدة لهذا المندوب.</div>
        @endif

        <div>
            <label class="block text-sm font-semibold mb-2"><x-info field="fleet.custody_return_lines" /> أصناف المرتجع</label>
            <div class="space-y-3">
                @for($i = 0; $i < 8; $i++)
                    <div class="grid sm:grid-cols-2 gap-3 items-end">
                        <div>
                            <x-searchable-select
                                name="lines[{{ $i }}][product_id]"
                                :options="collect($agentBalanceLines)->map(fn ($l) => ['value' => (string) $l->product_id, 'label' => $l->product_name])->all()"
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
            <a href="{{ route('fleet.custody.returns.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
        </div>
    </form>
</div>
@endsection
