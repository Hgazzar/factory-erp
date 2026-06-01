<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsAsHeadlessOrWeb;
use App\Http\Requests\StoreStockReceiptRequest;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Inventory\StockReceiptService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class StockInController extends Controller
{
    use RespondsAsHeadlessOrWeb;

    public function create(Request $request, StockReceiptService $stockReceipts): View|JsonResponse
    {
        try {
            $tenantUserId = $stockReceipts->resolveTenantUserId();
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 403, 'tenant_unresolved');
            }
            abort(403, $e->getMessage());
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success($stockReceipts->createFormOptions($tenantUserId));
        }

        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::query()->active()->orderBy('name_ar')->get();
        $items = Item::query()->active()->orderBy('code')->get(['id', 'code', 'name_ar', 'name_en']);

        return view('inventory.stock_in.create', compact('suppliers', 'warehouses', 'items'));
    }

    public function store(StoreStockReceiptRequest $request, StockReceiptService $stockReceipts): RedirectResponse|JsonResponse
    {
        try {
            $tenantUserId = $stockReceipts->resolveTenantUserId();
            $data = $request->validated();

            $stockIn = $stockReceipts->createReceipt(
                $tenantUserId,
                [
                    'supplier_id' => (int) $data['supplier_id'],
                    'settlement_type' => $data['settlement_type'],
                    'reference' => $data['reference'] ?? null,
                    'date' => $data['date'],
                    'notes' => $data['notes'] ?? null,
                ],
                $data['lines']
            );
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 422, 'stock_receipt_failed');
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success(
                $stockReceipts->toApiDetail($stockIn),
                201
            );
        }

        return redirect()
            ->route('inventory.stock-in.show', $stockIn)
            ->with('success', 'تم حفظ إذن الإضافة المخزني.')
            ->with('open_print', true);
    }

    public function show(Request $request, StockIn $stockIn, StockReceiptService $stockReceipts): View|JsonResponse
    {
        try {
            $tenantUserId = $stockReceipts->resolveTenantUserId();
            $stockIn = $stockReceipts->findReceiptForTenant($tenantUserId, (int) $stockIn->id);
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 404, 'stock_receipt_not_found');
            }
            abort(404, $e->getMessage());
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success($stockReceipts->toApiDetail($stockIn));
        }

        $stockIn->line_value_total = $stockIn->lines->sum(
            fn ($l) => (float) $l->quantity * (float) $l->purchase_price
        );

        return view('inventory.stock_in.show', [
            'stockIn' => $stockIn,
            'autoPrint' => $request->boolean('print') || (bool) session()->pull('open_print'),
        ]);
    }
}
