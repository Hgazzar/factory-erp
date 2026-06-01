@extends('layouts.app')

@section('title', 'مهام الفني - '.config('app.name'))

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">مهامي — الخدمات والصيانة</h1>
        <p class="text-sm text-gray-500 mt-1">الطلبات المسندة إليك والمفتوحة فقط.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><x-info field="services.ref_col" /> المرجع</th>
                        <th class="py-3 px-4 font-medium">النوع</th>
                        <th class="py-3 px-4 font-medium">الأولوية</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium">العميل</th>
                        <th class="py-3 px-4 font-medium">تحديث الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        @php
                            $typeLabels = ['install' => 'تركيب', 'maintenance' => 'صيانة', 'repair' => 'إصلاح'];
                            $st = ['assigned' => 'مسند', 'in_progress' => 'قيد التنفيذ', 'open' => 'مفتوح'];
                        @endphp
                        <tr class="border-b border-gray-100 {{ $o->priority === 'urgent' ? 'bg-red-50/40' : '' }}">
                            <td class="py-3 px-4 font-mono">{{ $o->reference_number }}</td>
                            <td class="py-3 px-4">{{ $typeLabels[$o->service_type] ?? $o->service_type }}</td>
                            <td class="py-3 px-4">{{ $o->priority === 'urgent' ? 'عاجل' : 'عادي' }}</td>
                            <td class="py-3 px-4">{{ $st[$o->status] ?? $o->status }}</td>
                            <td class="py-3 px-4">{{ $o->customer?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <form method="post" action="{{ route('services.technician.orders.update', $o) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" class="rounded-lg border-gray-300 text-xs py-1.5">
                                        <option value="assigned" @selected($o->status === 'assigned')>مسند</option>
                                        <option value="in_progress" @selected($o->status === 'in_progress')>قيد التنفيذ</option>
                                    </select>
                                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium">حفظ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-gray-500">لا توجد مهام مفتوحة مسندة إليك.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
