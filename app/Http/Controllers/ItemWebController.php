<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\UniversalImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemWebController extends Controller
{
    use PersistsMorphAttachments;

    /**
     * عرض قائمة الأصناف.
     */
    public function index(Request $request): View|StreamedResponse
    {
        $search = trim((string) $request->string('search'));
        $warehouseId = $request->integer('warehouse_id');
        $category = (string) $request->string('category');
        $status = (string) $request->string('status');

        $stockSubquery = '(SELECT COALESCE(SUM(iw.quantity), 0) FROM item_warehouse iw WHERE iw.item_id = items.id)';

        $query = Item::query()
            ->with(['unit:id,name_ar,code', 'warehouses:id,name_ar,name_en,code', 'attachments'])
            ->withSum('warehouses as total_stock', 'item_warehouse.quantity');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name_ar', 'like', '%'.$search.'%')
                    ->orWhere('name_en', 'like', '%'.$search.'%');
            });
        }

        if ($warehouseId > 0) {
            $query->whereHas('warehouses', function ($q) use ($warehouseId) {
                $q->where('warehouses.id', $warehouseId);
            });
        }

        if (in_array($category, Item::typeValues(), true)) {
            $query->where('type', $category);
        }

        if (in_array($status, ['available', 'low', 'out'], true)) {
            if ($status === 'out') {
                $query->whereRaw($stockSubquery.' <= 0');
            } elseif ($status === 'low') {
                $query->whereRaw($stockSubquery.' > 0')
                    ->whereRaw('COALESCE(items.min_stock, 0) > 0')
                    ->whereRaw($stockSubquery.' <= items.min_stock');
            } else {
                $query->whereRaw('('.$stockSubquery.' > COALESCE(items.min_stock, 0)) OR ('.$stockSubquery.' > 0 AND COALESCE(items.min_stock, 0) = 0)');
            }
        }

        $query->orderBy('code');

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        $items = $query->paginate(15)->withQueryString();
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

        return view('items.index', compact(
            'items',
            'warehouses',
            'categories',
            'statuses',
            'search',
            'warehouseId',
            'category',
            'status'
        ));
    }

    /**
     * عرض تفاصيل الصنف وإدارة BOM للمنتج التام.
     */
    public function show(Item $item): View
    {
        $item->load(['unit:id,name_ar,code', 'bomComponents.componentItem:id,code,name_ar,type', 'attachments']);

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
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $warehouseId = (int) $validated['warehouse_id'];
        $initialQuantity = $validated['initial_quantity'] ?? 0;
        unset($validated['warehouse_id'], $validated['initial_quantity']);

        if (array_key_exists('attachments', $validated)) {
            unset($validated['attachments']);
        }

        $uid = (int) auth()->id();
        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        DB::transaction(function () use ($validated, $warehouseId, $initialQuantity, $request, $uid, $uploads) {
            $item = Item::create(array_merge($validated, [
                'user_id' => $uid,
                'barcode' => $request->filled('barcode') ? $request->string('barcode')->toString() : null,
                'min_stock' => $validated['min_stock'] ?? 0,
                'cost' => 0,
                'selling_price' => $validated['selling_price'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]));

            $item->warehouses()->attach($warehouseId, [
                'quantity' => $initialQuantity,
                'reserved_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->persistMorphAttachments($item, $uploads, $uid, 'items');
        });

        return redirect()->route('items.index')->with('success', 'تم حفظ الصنف وربطه بالمستودع الافتراضي بنجاح');
    }

    public function edit(Item $item): View
    {
        $item->load(['attachments']);
        $units = Unit::active()->get();
        $warehouses = Warehouse::active()->get();

        return view('items.edit', compact('item', 'units', 'warehouses'));
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $data = $request->validated();
        if (array_key_exists('attachments', $data)) {
            unset($data['attachments']);
        }

        $uid = (int) auth()->id();
        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        DB::transaction(function () use ($request, $item, $data, $uid, $uploads) {
            $update = [
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

            $item->update(array_merge($update, [
                'user_id' => $uid,
            ]));

            $this->persistMorphAttachments($item, $uploads, $uid, 'items');
        });

        return redirect()->route('items.index')->with('success', 'تم تحديث الصنف بنجاح.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        // حذف الارتباط بالمخازن أولاً ثم حذف الصنف
        $item->warehouses()->detach();
        $item->delete();

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
