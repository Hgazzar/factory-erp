@extends('layouts.fleet')

@section('title', 'خط سير جديد — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-3xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">إنشاء خط سير</h1>

    @if($errors->has('route'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('route') }}</div>
    @endif

    <form method="POST" action="{{ route('fleet.routes.store') }}" class="fleet-card p-6 space-y-4">
        @csrf
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.route_date" /> التاريخ</label>
                <input type="date" name="route_date" value="{{ old('route_date', request('route_date', now()->toDateString())) }}" required class="w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.route_agent" /> المندوب</label>
                <x-searchable-select
                    name="agent_id"
                    :options="collect($agents)->map(fn ($a) => ['value' => (string) $a->id, 'label' => $a->name])->all()"
                    :selected="old('agent_id', '')"
                    empty-label="اختر المندوب"
                />
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-2"><x-info field="fleet.route_stops" /> عملاء خط السير</label>
            <p class="text-xs text-violet-700/70 mb-3">حدّد العملاء بالترتيب المطلوب للزيارة (من الأعلى للأسفل).</p>
            <div class="space-y-2 max-h-72 overflow-y-auto border border-violet-100 rounded-lg p-3">
                @forelse($customers as $customer)
                    <label class="flex items-center gap-2 text-sm py-1">
                        <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}"
                            @checked(in_array($customer->id, old('customer_ids', []), false))>
                        <span>{{ $customer->name }}@if($customer->city) <span class="text-violet-600/70">— {{ $customer->city }}</span>@endif</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">أضف عملاء ميدان أولاً من قائمة العملاء.</p>
                @endforelse
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.route_notes" /> ملاحظات</label>
            <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="fleet-btn fleet-btn-primary">حفظ خط السير</button>
            <a href="{{ route('fleet.routes.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
        </div>
    </form>
</div>
@endsection
