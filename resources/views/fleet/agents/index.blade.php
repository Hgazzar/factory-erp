@extends('layouts.fleet')

@section('title', niche_label('entities.agent', 'المناديب').' — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.nav_agents" /> {{ niche_label('entities.agent', 'المناديب') }}</h1>
            <p class="text-sm text-violet-700/80 mt-1">الإجمالي: {{ $listStats['total'] }} — نشط: {{ $listStats['active'] }}</p>
        </div>
        <a href="{{ route('fleet.agents.create') }}" class="fleet-btn fleet-btn-primary">إضافة مندوب</a>
    </div>

    <form method="GET" class="fleet-card p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[12rem]">
            <label class="block text-sm font-semibold mb-1">بحث</label>
            <input type="search" name="q" value="{{ $q }}" placeholder="اسم، جوال، بريد…" class="w-full rounded-lg border-gray-300">
        </div>
        <button type="submit" class="fleet-btn fleet-btn-soft">تصفية</button>
    </form>

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.agent_name" /> الاسم</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.agent_phone" /> الجوال</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.agent_email" /> البريد</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.agent_status" /> الحالة</th>
                    <th class="px-4 py-3 text-right font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($agents as $agent)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $agent->name }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $agent->phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $agent->email ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $agent->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $agent->isActive() ? 'نشط' : 'غير نشط' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('fleet.agents.edit', $agent) }}" class="text-violet-600 font-semibold hover:underline">تعديل</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا يوجد مناديب بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $agents->links() }}
</div>
@endsection
