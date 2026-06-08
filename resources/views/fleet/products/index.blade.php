@extends('layouts.fleet')

@section('title', 'الكتalog الخفيف — '.niche_module_label('fleet'))

@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-violet-950"><x-info field="fleet.nav_products" /> الكتalog الخفيف</h1>
            <p class="text-sm text-violet-700/80 mt-1">الإجمالي: {{ $listStats['total'] }} — نشط: {{ $listStats['active'] }}</p>
        </div>
        <a href="{{ route('fleet.products.create') }}" class="fleet-btn fleet-btn-primary">إضافة صنف</a>
    </div>

    <form method="GET" class="fleet-card p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[12rem]">
            <label class="block text-sm font-semibold mb-1">بحث</label>
            <input type="search" name="q" value="{{ $q }}" placeholder="اسم، SKU…" class="w-full rounded-lg border-gray-300">
        </div>
        <button type="submit" class="fleet-btn fleet-btn-soft">تصفية</button>
    </form>

    <div class="fleet-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-violet-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.product_name" /> الاسم</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.product_sku" /> SKU</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.product_sale_price" /> السعر</th>
                    <th class="px-4 py-3 text-right font-semibold"><x-info field="fleet.product_is_active" /> نشط</th>
                    <th class="px-4 py-3 text-right font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($products as $product)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $product->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku ?? '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ erp_money($product->sale_price) }}</td>
                        <td class="px-4 py-3">{{ $product->is_active ? 'نعم' : 'لا' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('fleet.products.edit', $product) }}" class="text-violet-600 font-semibold hover:underline">تعديل</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد أصناف بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $products->links() }}
</div>
@endsection
