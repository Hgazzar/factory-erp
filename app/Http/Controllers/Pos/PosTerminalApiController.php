<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\PosSale;
use App\Services\Pos\PosCatalogService;
use App\Services\Pos\PosSaleService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\PosFeatureKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class PosTerminalApiController extends Controller
{
    use ResolvesOperationsTenant;

    public function categories(Request $request, PosCatalogService $catalog): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return response()->json([
            'categories' => $catalog->activeCategories($tenantUserId),
        ]);
    }

    public function products(Request $request, PosCatalogService $catalog): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;

        return response()->json([
            'products' => $catalog->searchProducts(
                $tenantUserId,
                $validated['q'] ?? null,
                $categoryId,
                (int) ($validated['limit'] ?? 48),
            ),
        ]);
    }

    public function lookup(Request $request, PosCatalogService $catalog): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:64'],
        ]);

        $product = $catalog->findByBarcode($tenantUserId, $validated['barcode']);

        if ($product === null) {
            return response()->json([
                'message' => 'لم يُعثر على منتج بهذا الباركود.',
            ], 404);
        }

        return response()->json([
            'product' => $catalog->productPayload($product),
        ]);
    }

    public function checkout(
        Request $request,
        PosSaleService $sales,
        TenantFeatureRegistry $features,
    ): JsonResponse {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'pos_device_id' => ['required', 'integer', 'min:1'],
            'pos_session_id' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,card,bank,mixed'],
            'payment_splits' => ['nullable', 'array'],
            'payment_splits.*.method' => ['required_with:payment_splits', 'in:cash,card,bank'],
            'payment_splits.*.amount' => ['required_with:payment_splits', 'numeric', 'gt:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.pos_product_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $canOverridePrice = $features->isEnabled(PosFeatureKeys::MANUAL_PRICE_OVERRIDE, $tenantUserId);

        $lines = [];
        foreach ($validated['lines'] as $line) {
            $entry = [
                'pos_product_id' => (int) $line['pos_product_id'],
                'quantity' => (float) $line['quantity'],
            ];

            if (array_key_exists('unit_price', $line) && $line['unit_price'] !== null) {
                if (! $canOverridePrice) {
                    return response()->json([
                        'message' => 'تعديل السعر من الشاشة غير متاح في باقتك الحالية.',
                    ], 403);
                }

                $entry['unit_price'] = (float) $line['unit_price'];
            }

            $lines[] = $entry;
        }

        try {
            $sale = $sales->processSale($tenantUserId, [
                'pos_device_id' => (int) $validated['pos_device_id'],
                'pos_session_id' => isset($validated['pos_session_id']) ? (int) $validated['pos_session_id'] : null,
                'payment_method' => $validated['payment_method'],
                'payment_splits' => $validated['payment_splits'] ?? [],
                'lines' => $lines,
            ], (int) auth()->id());

            return response()->json([
                'sale' => $this->salePayload($sale),
                'receipt_url' => route('pos.terminal.receipt', $sale),
            ], 201);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function salePayload(PosSale $sale): array
    {
        $sale->loadMissing(['items.product']);

        return [
            'id' => (int) $sale->id,
            'receipt_number' => $sale->receipt_number,
            'invoice_number' => $sale->invoice_number,
            'payment_method' => $sale->payment_method,
            'subtotal_amount' => (float) $sale->subtotal_amount,
            'vat_amount' => (float) $sale->vat_amount,
            'total_amount' => (float) $sale->total_amount,
            'items' => $sale->items->map(static fn ($item): array => [
                'product_name' => $item->product?->name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->values()->all(),
        ];
    }
}
