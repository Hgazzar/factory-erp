<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Controllers\Concerns\RespondsAsHeadlessOrWeb;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryItemService;
use App\Services\UniversalImportService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemWebController extends Controller
{
    use PersistsMorphAttachments;
    use RespondsAsHeadlessOrWeb;

    /**
     * عرض قائمة الأصناف (Blade) أو JSON للـ Headless API.
     */
    public function index(Request $request, InventoryItemService $inventoryItems): View|StreamedResponse|JsonResponse
    {
        try {
            $tenantUserId = $inventoryItems->resolveTenantUserId();
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 403, 'tenant_unresolved');
            }
            abort(403, $e->getMessage());
        }

        $filters = [
            'search' => trim((string) $request->string('search')),
            'warehouse_id' => $request->integer('warehouse_id'),
            'category' => (string) $request->string('category'),
            'status' => (string) $request->string('status'),
            'per_page' => $request->integer('per_page') ?: 15,
        ];

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($inventoryItems->listItemsForExport($tenantUserId, $filters));
        }

        if ($this->wantsApiResponse($request)) {
            $paginator = $inventoryItems->paginateItems($tenantUserId, $filters);

            return ApiResponse::paginated(
                $paginator,
                fn (Item $item) => $inventoryItems->toApiSummary($item),
                [
                    'filters' => [
                        'search' => $filters['search'],
                        'warehouse_id' => $filters['warehouse_id'] ?: null,
                        'category' => $filters['category'] ?: null,
                        'status' => $filters['status'] ?: null,
                    ],
                ]
            );
        }

        $items = $inventoryItems->paginateItems($tenantUserId, $filters);
        $warehouses = Warehouse::query()->active()->orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'code']);

        $categories = [
            Item::TYPE_RAW_MATERIAL => 'مواد خام',
            Item::TYPE_FINISHED_GOOD => 'منتج تام',
            Item::TYPE_SERVICE => 'خدمة',
        ];

        $statuses = [
            'available' => 'متوفر',
            'low' => 'منخفض المخزون',
            'out' => 'نفاد الكمية',
        ];

        return view('items.index', [
            'items' => $items,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'statuses' => $statuses,
            'search' => $filters['search'],
            'warehouseId' => $filters['warehouse_id'],
            'category' => $filters['category'],
            'status' => $filters['status'],
        ]);
    }

    /**
     * عرض تفاصيل الصنف (Blade) أو JSON مع كميات المستودعات.
     */
    public function show(Request $request, Item $item, InventoryItemService $inventoryItems): View|JsonResponse
    {
        try {
            $tenantUserId = $inventoryItems->resolveTenantUserId();
            $item = $inventoryItems->findItemForTenant($tenantUserId, (int) $item->id);
            $quantities = $inventoryItems->warehouseQuantities($tenantUserId, (int) $item->id);
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 404, 'item_not_found');
            }
            abort(404, $e->getMessage());
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success(
                $inventoryItems->toApiDetail($item, $quantities)
            );
        }

        $rawMaterials = Item::query()
            ->active()
            ->ofType(Item::TYPE_RAW_MATERIAL)
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar']);

        $rawMaterialOptions = $rawMaterials->map(fn ($i) => [
            'id' => $i->id,
            'label' => $i->code.' — '.$i->name_ar,
        ])->values()->all();

        $bomInitialRows = $item->bomComponents->map(function ($c) {
            $q = (float) $c->quantity_per_unit;

            return [
                'component_item_id' => (string) $c->component_item_id,
                'quantity_per_unit' => rtrim(rtrim(number_format($q, 4, '.', ''), '0'), '.') ?: '0',
            ];
        })->values()->all();

        return view('items.show', compact('item', 'rawMaterials', 'rawMaterialOptions', 'bomInitialRows'));
    }

    /**
     * نموذج إضافة صنف جديد.
     */
    public function create(): View
    {
        // في حالة عدم وجود وحدات على السيرفر (مثلاً بعد نشر جديد)، نقوم بزرع وحدات افتراضية فوراً
        if (Unit::count() === 0) {
            $defaultUnits = [
                // وحدات عدّ
                ['code' => 'EA', 'name_ar' => 'وحدة', 'name_en' => 'Each', 'symbol' => 'وحدة'],
                ['code' => 'PCS', 'name_ar' => 'قطعة', 'name_en' => 'Piece', 'symbol' => 'قطعة'],
                // وحدات وزن
                ['code' => 'KG', 'name_ar' => 'كيلوغرام', 'name_en' => 'Kilogram', 'symbol' => 'كجم'],
                // وحدات طول
                ['code' => 'M', 'name_ar' => 'متر', 'name_en' => 'Meter', 'symbol' => 'م'],
                // وحدات حجم/سوائل
                ['code' => 'L', 'name_ar' => 'لتر', 'name_en' => 'Litre', 'symbol' => 'لتر'],
            ];

            foreach ($defaultUnits as $u) {
                Unit::firstOrCreate(
                    ['code' => $u['code']],
                    [
                        'name_ar' => $u['name_ar'],
                        'name_en' => $u['name_en'],
                        'symbol' => $u['symbol'],
                        'conversion_factor' => 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        $units = Unit::active()->orderBy('name_ar')->get();
        $warehouses = Warehouse::active()->orderBy('name_ar')->get();

        return view('items.create', compact('units', 'warehouses'));
    }

    /**
     * حفظ صنف جديد وربطه بالمخزن الافتراضي.
     */
    public function store(StoreItemRequest $request, InventoryItemService $inventoryItems): RedirectResponse|JsonResponse
    {
        try {
            $tenantUserId = $inventoryItems->resolveTenantUserId();
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 403, 'tenant_unresolved');
            }
            abort(403, $e->getMessage());
        }

        $validated = $request->validated();
        $warehouseId = (int) $validated['warehouse_id'];
        $initialQuantity = (float) ($validated['initial_quantity'] ?? 0);
        unset($validated['warehouse_id'], $validated['initial_quantity']);

        if (array_key_exists('attachments', $validated)) {
            unset($validated['attachments']);
        }

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $attributes = array_merge($validated, [
            'barcode' => $request->filled('barcode') ? $request->string('barcode')->toString() : null,
            'min_stock' => $validated['min_stock'] ?? 0,
            'cost' => 0,
            'selling_price' => $validated['selling_price'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $item = $inventoryItems->createItem($tenantUserId, $attributes, $warehouseId, $initialQuantity);

        if ($uploads !== []) {
            $this->persistMorphAttachments($item, $uploads, $tenantUserId, 'items');
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success(
                ['item' => $inventoryItems->toApiSummary($item->fresh(['unit:id,name_ar,code']))],
                201
            );
        }

        return redirect()->route('items.index')->with('success', 'تم حفظ الصنف وربطه بالمستودع الافتراضي بنجاح');
    }

    public function edit(Item $item): View
    {
        $item->load(['attachments']);
        $units = Unit::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('items.edit', compact('item', 'units', 'warehouses'));
    }

    public function update(UpdateItemRequest $request, Item $item, InventoryItemService $inventoryItems): RedirectResponse|JsonResponse
    {
        try {
            $tenantUserId = $inventoryItems->resolveTenantUserId();
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 403, 'tenant_unresolved');
            }
            abort(403, $e->getMessage());
        }

        $data = $request->validated();
        if (array_key_exists('attachments', $data)) {
            unset($data['attachments']);
        }

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $attributes = [
            'code' => $data['code'],
            'barcode' => $data['barcode'] ?? null,
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'unit_id' => $data['unit_id'],
            'type' => $data['type'],
            'min_stock' => $data['min_stock'] ?? 0,
            'supplier' => $data['supplier'] ?? null,
            'material_type' => $data['material_type'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        try {
            $item = $inventoryItems->updateItem($tenantUserId, (int) $item->id, $attributes);
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                return ApiResponse::error($e->getMessage(), 404, 'item_not_found');
            }
            abort(404, $e->getMessage());
        }

        if ($uploads !== []) {
            $this->persistMorphAttachments($item, $uploads, $tenantUserId, 'items');
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success([
                'item' => $inventoryItems->toApiSummary($item->fresh(['unit:id,name_ar,code'])),
            ]);
        }

        return redirect()->route('items.index')->with('success', 'تم تحديث الصنف بنجاح.');
    }

    public function destroy(Request $request, Item $item, InventoryItemService $inventoryItems): RedirectResponse|JsonResponse
    {
        try {
            $tenantUserId = $inventoryItems->resolveTenantUserId();
            $inventoryItems->deleteItem($tenantUserId, (int) $item->id);
        } catch (RuntimeException $e) {
            if ($this->wantsApiResponse($request)) {
                $status = str_contains($e->getMessage(), 'غير موجود') ? 404 : 403;

                return ApiResponse::error($e->getMessage(), $status, 'item_delete_failed');
            }
            abort(str_contains($e->getMessage(), 'غير موجود') ? 404 : 403, $e->getMessage());
        }

        if ($this->wantsApiResponse($request)) {
            return ApiResponse::success(['deleted' => true]);
        }

        return redirect()->route('items.index')->with('success', 'تم حذف الصنف.');
    }

    private function exportCsv($items): StreamedResponse
    {
        $filename = 'items-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($items) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['SKU', 'Product Name', 'Category', 'Selling Price', 'Total Stock', 'Status']);

            foreach ($items as $item) {
                $stock = (float) ($item->total_stock ?? 0);
                $minStock = (float) ($item->min_stock ?? 0);
                $status = $stock <= 0
                    ? 'Out of stock'
                    : (($minStock > 0 && $stock <= $minStock) ? 'Low stock' : 'Available');

                fputcsv($handle, [
                    $item->code,
                    $item->name_ar ?: $item->name_en,
                    match ($item->type) {
                        Item::TYPE_RAW_MATERIAL => 'Raw material',
                        Item::TYPE_FINISHED_GOOD => 'Finished good',
                        Item::TYPE_SERVICE => 'Service',
                        default => 'Unknown',
                    },
                    number_format((float) data_get($item, 'selling_price', $item->cost ?? 0), 2, '.', ''),
                    number_format($stock, 2, '.', ''),
                    $status,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importTemplate(): StreamedResponse
    {
        $filename = 'items-import-template.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'code',
                'barcode',
                'name_ar',
                'name_en',
                'type',
                'cost',
                'selling_price',
                'min_stock',
                'supplier',
                'material_type',
                'is_active',
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_PRODUCTS);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('items.index')
            ->with('success', "تم استيراد الأصناف. نجاح: {$summary['created']} إضافة، {$summary['updated']} تحديث. فشل: {$summary['failed']}.")
            ->with('import_result', $summary);
    }
}
