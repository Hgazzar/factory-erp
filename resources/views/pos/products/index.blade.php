@extends('layouts.pos')

@section('title', 'منتجات نقاط البيع — '.config('app.name'))

@section('content')
<div class="space-y-6" dir="rtl">
    <x-flash-messages />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-gray-900">منتجات POS والمتجر</h1>
        <a href="{{ route('settings.store.edit') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">إعدادات المتجر ←</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
        <h2 class="text-base font-bold text-gray-900 mb-4">إضافة منتج سريع</h2>
        <form method="POST" action="{{ route('pos.products.store') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block"><x-info field="store.product_name" /> الاسم</label>
                <input type="text" name="name" required class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block"><x-info field="store.product_sku" /> SKU</label>
                <input type="text" name="sku" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block"><x-info field="store.product_barcode" /> باركود</label>
                <input type="text" name="barcode" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block"><x-info field="store.product_sale_price" /> سعر البيع</label>
                <input type="number" step="0.01" min="0" name="sale_price" required class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 mb-1 block"><x-info field="store.product_stock" /> الكمية</label>
                <input type="number" step="0.0001" min="0" name="current_quantity" value="0" class="w-full rounded-lg border-gray-300 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_published_online" value="0">
                    <input type="checkbox" name="is_published_online" value="1" class="rounded border-gray-300">
                    <span class="inline-flex items-center gap-1"><x-info field="store.is_published_online" /> نشر أونلاين</span>
                </label>
            </div>
            <div class="md:col-span-2 lg:col-span-3">
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">إضافة</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right min-w-[720px]">
                <thead class="bg-gray-50 text-gray-600 border-b">
                    <tr>
                        <th class="py-3 px-4 font-semibold"><x-info field="store.product_name" /></th>
                        <th class="py-3 px-4 font-semibold"><x-info field="store.product_sku" /></th>
                        <th class="py-3 px-4 font-semibold">السعر</th>
                        <th class="py-3 px-4 font-semibold"><x-info field="store.product_stock" /></th>
                        <th class="py-3 px-4 font-semibold"><x-info field="store.is_published_online" /></th>
                        <th class="py-3 px-4 font-semibold">المتجر</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="border-b border-gray-100 hover:bg-gray-50/80">
                            <td class="py-3 px-4 font-medium">{{ $product->name }}</td>
                            <td class="py-3 px-4 text-gray-500">{{ $product->sku ?? $product->barcode ?? '—' }}</td>
                            <td class="py-3 px-4 tabular-nums">{{ $erpCurrencyCode }} {{ number_format((float) $product->sale_price, 2) }}</td>
                            <td class="py-3 px-4 tabular-nums">{{ rtrim(rtrim(number_format((float) $product->current_quantity, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                            <td class="py-3 px-4">
                                <form method="POST" action="{{ route('pos.products.online', $product) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_published_online" value="0">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="is_published_online" value="1" class="rounded border-gray-300"
                                               @checked($product->is_published_online) onchange="this.form.submit()">
                                        <span class="text-xs">{{ $product->is_published_online ? 'منشور' : 'مخفي' }}</span>
                                    </label>
                                </form>
                            </td>
                            <td class="py-3 px-4 align-top">
                                <details class="text-xs">
                                    <summary class="cursor-pointer text-indigo-600 font-semibold">تحسين العرض</summary>
                                    <form method="POST" action="{{ route('pos.products.update', $product) }}" class="mt-2 space-y-2 min-w-[220px]">
                                        @csrf
                                        @method('PATCH')
                                        <input type="url" name="image_url" value="{{ $product->image_url }}" placeholder="رابط الصورة" class="w-full rounded border-gray-300 text-xs" dir="ltr">
                                        <input type="number" step="0.01" name="compare_at_price" value="{{ $product->compare_at_price }}" placeholder="سعر قبل الخصم" class="w-full rounded border-gray-300 text-xs">
                                        <input type="text" name="seo_title" value="{{ $product->seo_title }}" placeholder="SEO عنوان" class="w-full rounded border-gray-300 text-xs">
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="is_featured" value="1" @checked($product->is_featured) class="rounded"><x-info field="store.product_featured" /></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="is_trending" value="1" @checked($product->is_trending) class="rounded"><x-info field="store.product_trending" /></label>
                                        <label class="inline-flex items-center gap-1"><input type="checkbox" name="is_bestseller" value="1" @checked($product->is_bestseller) class="rounded"><x-info field="store.product_bestseller" /></label>
                                        <button type="submit" class="rounded bg-gray-800 px-2 py-1 text-white text-xs">حفظ</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-500">لا توجد منتجات بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="p-4 border-t">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
