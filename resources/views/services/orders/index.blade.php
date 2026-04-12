@extends('layouts.app')

@section('title', 'طلبات الخدمة - MIRADA ERP')

@section('content')
<div class="max-w-full" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">طلبات الخدمة والصيانة</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('services.dashboard') }}" class="px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">لوحة الخدمات</a>
            <a href="{{ route('services.orders.create') }}" class="px-4 py-2.5 rounded-lg text-white text-sm font-medium shadow-sm" style="background: #2563eb;">طلب جديد</a>
        </div>
    </div>

    <form method="get" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">الحالة</label>
            <select name="status" class="rounded-lg border-gray-300 text-sm">
                <option value="">الكل</option>
                <option value="open" @selected(request('status')==='open')>مفتوح</option>
                <option value="assigned" @selected(request('status')==='assigned')>مسند لفني</option>
                <option value="in_progress" @selected(request('status')==='in_progress')>قيد التنفيذ</option>
                <option value="completed" @selected(request('status')==='completed')>مكتمل</option>
                <option value="cancelled" @selected(request('status')==='cancelled')>ملغى</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">الأولوية</label>
            <select name="priority" class="rounded-lg border-gray-300 text-sm">
                <option value="">الكل</option>
                <option value="normal" @selected(request('priority')==='normal')>عادي</option>
                <option value="urgent" @selected(request('priority')==='urgent')>عاجل</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm">تصفية</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200">
                    <tr>
                        <th class="py-3 px-4 font-medium"><x-info field="services.ref_col" /> المرجع</th>
                        <th class="py-3 px-4 font-medium">النوع</th>
                        <th class="py-3 px-4 font-medium">الأولوية</th>
                        <th class="py-3 px-4 font-medium">الحالة</th>
                        <th class="py-3 px-4 font-medium">الفني</th>
                        <th class="py-3 px-4 font-medium">مدفوعة</th>
                        <th class="py-3 px-4 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $o)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="py-3 px-4 font-mono">{{ $o->reference_number }}</td>
                            <td class="py-3 px-4">{{ match($o->service_type) { 'install' => 'تركيب', 'maintenance' => 'صيانة', 'repair' => 'إصلاح', default => $o->service_type } }}</td>
                            <td class="py-3 px-4">{{ $o->priority === 'urgent' ? 'عاجل' : 'عادي' }}</td>
                            <td class="py-3 px-4">{{ match($o->status) { 'open' => 'مفتوح', 'assigned' => 'مسند لفني', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'cancelled' => 'ملغى', default => $o->status } }}</td>
                            <td class="py-3 px-4">{{ $o->assignedTechnician?->name ?? '—' }}</td>
                            <td class="py-3 px-4">{{ $o->is_paid_service ? 'نعم' : 'لا' }}</td>
                            <td class="py-3 px-4"><a href="{{ route('services.orders.show', $o) }}" class="text-indigo-600 hover:underline">عرض</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
