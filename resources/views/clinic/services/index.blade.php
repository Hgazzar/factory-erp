@extends('layouts.clinic')

@section('title', 'دليل الخدمات — '.config('app.name'))

@section('content')
<div class="space-y-6" dir="rtl">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif

    <h1 class="text-2xl font-bold text-teal-950"><x-info field="clinic.services_catalog" /> دليل الخدمات والأسعار</h1>

    <div class="rounded-xl border border-teal-100 bg-white shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-teal-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold">الكود</th>
                    <th class="px-4 py-3 text-right font-semibold">الخدمة</th>
                    <th class="px-4 py-3 text-right font-semibold">السعر</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="clinic.vat_inclusive" /> شامل ض.ق.م</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($services as $svc)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs">{{ $svc->code }}</td>
                        <td class="px-4 py-3">{{ $svc->name }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($svc->price) }}</td>
                        <td class="px-4 py-3">{{ $svc->vat_inclusive ? 'نعم' : 'لا' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('clinic.services.store') }}" class="rounded-xl border border-teal-100 bg-white p-6 max-w-xl space-y-3">
        @csrf
        <h2 class="font-semibold">إضافة خدمة</h2>
        <input type="text" name="code" placeholder="CODE" required class="w-full rounded-lg border-gray-300">
        <input type="text" name="name" placeholder="اسم الخدمة" required class="w-full rounded-lg border-gray-300">
        <input type="number" name="price" step="0.01" min="0" placeholder="السعر" required class="w-full rounded-lg border-gray-300">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="vat_inclusive" value="1" checked> السعر شامل ض.ق.م</label>
        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">إضافة</button>
    </form>
</div>
@endsection
