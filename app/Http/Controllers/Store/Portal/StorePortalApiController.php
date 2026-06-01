<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store\Portal;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Services\Store\StoreCartQuoteService;
use App\Services\Store\StoreCheckoutService;
use App\Services\Store\StoreCouponService;
use App\Services\Store\StorefrontCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class StorePortalApiController extends Controller
{
    public function categories(Request $request, StorefrontCatalogService $catalog): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');

        return response()->json(['categories' => $catalog->categories($tenantUserId)]);
    }

    public function products(Request $request, StorefrontCatalogService $catalog): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', 'string', 'in:newest,price_asc,price_desc,rating,name'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:8', 'max:48'],
            'featured' => ['nullable', 'boolean'],
            'trending' => ['nullable', 'boolean'],
            'bestseller' => ['nullable', 'boolean'],
        ]);

        $paginator = $catalog->paginatedProducts($tenantUserId, [
            'q' => $validated['q'] ?? null,
            'category_id' => isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            'sort' => $validated['sort'] ?? 'newest',
            'min_price' => isset($validated['min_price']) ? (float) $validated['min_price'] : null,
            'max_price' => isset($validated['max_price']) ? (float) $validated['max_price'] : null,
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 12),
            'featured' => (bool) ($validated['featured'] ?? false),
            'trending' => (bool) ($validated['trending'] ?? false),
            'bestseller' => (bool) ($validated['bestseller'] ?? false),
        ]);

        return response()->json([
            'currency' => CompanySetting::resolvedCurrencyCode($tenantUserId),
            'products' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function showProduct(Request $request, string $tenant_slug, int|string $product, StorefrontCatalogService $catalog): JsonResponse
    {
        $product = (int) $product;
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');
        $slug = (string) $request->route('tenant_slug');

        $model = $catalog->findPublishedProduct($tenantUserId, $product);
        if ($model === null) {
            return response()->json(['message' => 'المنتج غير موجود.'], 404);
        }

        $detail = $catalog->detailPayload($model, $slug);
        $detail['related'] = $catalog->productList($tenantUserId, 4);

        return response()->json(['product' => $detail, 'currency' => CompanySetting::resolvedCurrencyCode($tenantUserId)]);
    }

    public function quote(Request $request, StoreCartQuoteService $quote): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');

        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.pos_product_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'coupon_code' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $result = $quote->quote($tenantUserId, $validated['lines'], $validated['coupon_code'] ?? null);

            return response()->json([
                'currency' => CompanySetting::resolvedCurrencyCode($tenantUserId),
                'lines' => $result['display_lines'],
                'subtotal' => $result['subtotal'],
                'vat' => $result['vat'],
                'discount' => $result['discount'],
                'total' => $result['total'],
                'coupon' => $result['coupon'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function applyCoupon(Request $request, StoreCouponService $coupons): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $coupon = $coupons->findValid($tenantUserId, $validated['code']);
            $discount = $coupons->calculateDiscount($coupon, (float) $validated['subtotal']);

            return response()->json([
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
                'discount' => $discount,
                'message' => 'تم تطبيق الكود بنجاح.',
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function checkout(Request $request, StoreCheckoutService $checkout): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'customer_address' => ['required', 'string', 'max:2000'],
            'coupon_code' => ['nullable', 'string', 'max:32'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.pos_product_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $lines = array_map(static fn (array $line): array => [
            'pos_product_id' => (int) $line['pos_product_id'],
            'quantity' => (float) $line['quantity'],
        ], $validated['lines']);

        try {
            $sale = $checkout->placeOnlineOrder($tenantUserId, [
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'address' => $validated['customer_address'],
            ], $lines, $validated['coupon_code'] ?? null);

            return response()->json([
                'order' => [
                    'id' => (int) $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => (float) $sale->total_amount,
                    'discount_amount' => (float) ($sale->discount_amount ?? 0),
                    'payment_method' => $sale->payment_method,
                ],
                'success_url' => route('store.portal.order.success', [
                    'tenant_slug' => $request->route('tenant_slug'),
                    'saleId' => $sale->id,
                ]),
            ], 201);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
