<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * عرض قائمة الأصناف.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Item::query()->with(['unit', 'warehouses']);

        if ($request->boolean('active_only')) {
            $query->active();
        }
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name_ar', 'ilike', "%{$search}%")
                    ->orWhere('name_en', 'ilike', "%{$search}%");
            });
        }

        $items = $query->orderBy('code')->paginate($request->input('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء صنف جديد.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = Item::create($request->validated());

        return response()->json($item->load('unit'), 201);
    }

    /**
     * عرض صنف (مع الوحدة والكميات في المخازن).
     */
    public function show(Item $item): JsonResponse
    {
        $item->load(['unit', 'warehouses', 'itemWarehouses.warehouse']);

        return response()->json([
            ...$item->toArray(),
            'total_quantity' => $item->total_quantity,
            'total_reserved_quantity' => $item->total_reserved_quantity,
        ]);
    }

    /**
     * تحديث صنف.
     */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        $item->update($request->validated());

        return response()->json($item->fresh(['unit', 'warehouses']));
    }

    /**
     * حذف صنف.
     */
    public function destroy(Item $item): JsonResponse
    {
        $item->itemWarehouses()->delete();
        $item->delete();

        return response()->json(null, 204);
    }
}
