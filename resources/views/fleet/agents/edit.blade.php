@extends('layouts.fleet')

@section('title', 'تعديل مندوب — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6 max-w-2xl" dir="rtl">
    <h1 class="text-2xl font-extrabold text-violet-950">تعديل {{ $agent->name }}</h1>

    <form method="POST" action="{{ route('fleet.agents.update', $agent) }}" class="fleet-card p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.agent_name" /> الاسم</label>
            <input type="text" name="name" value="{{ old('name', $agent->name) }}" required class="w-full rounded-lg border-gray-300">
            @error('name')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.agent_phone" /> الجوال</label>
            <input type="text" name="phone" value="{{ old('phone', $agent->phone) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.agent_email" /> البريد</label>
            <input type="email" name="email" value="{{ old('email', $agent->email) }}" class="w-full rounded-lg border-gray-300" dir="ltr">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.agent_status" /> الحالة</label>
            <x-searchable-select
                name="status"
                :options="$statusOptions"
                :selected="old('status', $agent->status)"
                :searchable="false"
                label=""
            />
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><x-info field="fleet.agent_notes" /> ملاحظات</label>
            <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300">{{ old('notes', $agent->notes) }}</textarea>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="fleet-btn fleet-btn-primary">حفظ</button>
            <a href="{{ route('fleet.agents.index') }}" class="fleet-btn fleet-btn-soft">إلغاء</a>
        </div>
    </form>
</div>
@endsection
