@extends('layouts.fleet')

@section('title', 'تعديل عميل — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-2xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">تعديل {{ $customer->name }}</h1>

    <form method="POST" action="{{ route('fleet.customers.update', $customer) }}" class="fleet-card p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_name" /> الاسم</label>
            <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="w-full rounded-lg border-gray-300">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_phone" /> الجوال</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_email" /> البريد</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_address" /> العنوان</label>
            <input type="text" name="address" value="{{ old('address', $customer->address) }}" class="w-full rounded-lg border-gray-300">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_region" /> المنطقة</label>
                <input type="text" name="region" value="{{ old('region', $customer->region) }}" class="w-full rounded-lg border-gray-300">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_city" /> المدينة</label>
                <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="w-full rounded-lg border-gray-300">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_assigned_agent" /> المندوب</label>
            <x-searchable-select
                name="assigned_agent_id"
                :options="$agentOptions"
                :selected="old('assigned_agent_id', (string) ($customer->assigned_agent_id ?? ''))"
                empty-label="بدون تعيين"
                empty-option
            />
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_status" /> الحالة</label>
            <x-searchable-select
                name="status"
                :options="$statusOptions"
                :selected="old('status', $customer->status)"
                :searchable="false"
                label=""
            />
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.customer_notes" /> ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300">{{ old('notes', $customer->notes) }}</textarea>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="fleet-btn fleet-btn-primary">حفظ</button>
            <a href="{{ route('fleet.customers.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
        </div>
    </form>
</div>
@endsection
