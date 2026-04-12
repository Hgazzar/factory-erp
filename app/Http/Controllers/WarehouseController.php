<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * عرض قائمة المخازن.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        if ($request->boolean('active_only')) {
            $query->active();
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name_ar', 'ilike', "%{$search}%")
                    ->orWhere('name_en', 'ilike', "%{$search}%");
            });
        }

        $warehouses = $query->orderBy('code')->paginate($request->input('per_page', 15));

        return response()->json($warehouses);
    }

    /**
     * إنشاء مخزن جديد.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());

        return response()->json($warehouse, 201);
    }

    /**
     * عرض مخزن (مع الأصناف والكميات إن وُجدت).
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        $warehouse->load('items');

        return response()->json($warehouse);
    }

    /**
     * تحديث مخزن.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse->update($request->validated());

        return response()->json($warehouse->fresh());
    }

    /**
     * حذف مخزن.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->items()->detach();
        $warehouse->delete();

        return response()->json(null, 204);
    }
}
