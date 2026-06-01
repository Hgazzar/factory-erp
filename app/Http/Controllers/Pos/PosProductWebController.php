<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\PosProduct;
use App\Models\PosProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PosProductWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $products = PosProduct::query()
            ->where('user_id', $tenantUserId)
            ->with(['category:id,name'])
            ->orderBy('name')
            ->paginate(30);

        return view('pos.products.index', compact('products'));
    }

    public function updateOnlineVisibility(Request $request, PosProduct $posProduct): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_if((int) $posProduct->user_id !== $tenantUserId, 403);

        $validated = $request->validate([
            'is_published_online' => ['required', 'boolean'],
        ]);

        $posProduct->is_published_online = (bool) $validated['is_published_online'];
        $posProduct->save();

        return back()->with('success', 'تم تحديث ظهور المنتج في المتجر.');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pos_product_category_id' => ['nullable', 'integer', 'exists:pos_product_categories,id'],
            'sku' => ['nullable', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'sale_price' => ['required', 'numeric', 'gte:0'],
            'cost_price' => ['nullable', 'numeric', 'gte:0'],
            'vat_percent' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'current_quantity' => ['nullable', 'numeric', 'gte:0'],
            'is_published_online' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['pos_product_category_id'])) {
            $ownsCategory = PosProductCategory::query()
                ->where('user_id', $tenantUserId)
                ->whereKey((int) $validated['pos_product_category_id'])
                ->exists();
            if (! $ownsCategory) {
                return back()->withErrors(['pos_product_category_id' => 'الفئة غير صالحة.'])->withInput();
            }
        }

        $qty = round((float) ($validated['current_quantity'] ?? 0), 4);

        PosProduct::query()->create([
            'user_id' => $tenantUserId,
            'pos_product_category_id' => $validated['pos_product_category_id'] ?? null,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'cost_price' => round((float) ($validated['cost_price'] ?? 0), 4),
            'sale_price' => round((float) $validated['sale_price'], 4),
            'vat_percent' => round((float) ($validated['vat_percent'] ?? 15), 4),
            'opening_quantity' => $qty,
            'current_quantity' => $qty,
            'low_stock_alert_quantity' => 0,
            'is_active' => true,
            'is_published_online' => $request->boolean('is_published_online'),
        ]);

        return back()->with('success', 'تم إضافة المنتج.');
    }

    public function update(Request $request, PosProduct $posProduct): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_if((int) $posProduct->user_id !== $tenantUserId, 403);

        $validated = $request->validate([
            'image_url' => ['nullable', 'string', 'max:512'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:0'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:50000'],
            'is_featured' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'is_bestseller' => ['nullable', 'boolean'],
        ]);

        $posProduct->fill([
            'image_url' => $validated['image_url'] ?? $posProduct->image_url,
            'compare_at_price' => isset($validated['compare_at_price']) ? round((float) $validated['compare_at_price'], 4) : $posProduct->compare_at_price,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'description' => $validated['description'] ?? $posProduct->description,
            'is_featured' => $request->boolean('is_featured'),
            'is_trending' => $request->boolean('is_trending'),
            'is_bestseller' => $request->boolean('is_bestseller'),
        ]);
        $posProduct->save();

        return back()->with('success', 'تم تحديث بيانات المتجر للمنتج.');
    }
}
