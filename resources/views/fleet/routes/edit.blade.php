@extends('layouts.fleet')

@section('title', 'تعديل خط سير — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-3xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">تعديل خط سير — {{ $route->agent?->name }}</h1>

    @if($errors->has('route'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errors->first('route') }}</div>
    @endif

    <form method="POST" action="{{ route('fleet.routes.update', $route) }}" class="fleet-card p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-semibold mb-2"><x-info field="fleet.route_stops" /> عملاء خط السير</label>
            <div class="space-y-2 max-h-72 overflow-y-auto border border-violet-100 rounded-lg p-3">
                @foreach($customers as $customer)
                    <label class="flex items-center gap-2 text-sm py-1">
                        <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}"
                            @checked(in_array($customer->id, old('customer_ids', $selectedCustomerIds), false))>
                        <span>{{ $customer->name }}@if($customer->city) <span class="text-violet-600/70">— {{ $customer->city }}</span>@endif</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.route_notes" /> ملاحظات</label>
            <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300">{{ old('notes', $route->notes) }}</textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="fleet-btn fleet-btn-primary">حفظ</button>
            <a href="{{ route('fleet.routes.show', $route) }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
        </div>
    </form>
</div>
@endsection
