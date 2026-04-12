<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * عرض قائمة الوحدات.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::query()->with('baseUnit');

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

        $units = $query->orderBy('code')->paginate($request->input('per_page', 15));

        return response()->json($units);
    }

    /**
     * إنشاء وحدة جديدة.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $unit = Unit::create($request->validated());

        return response()->json($unit->load('baseUnit'), 201);
    }

    /**
     * عرض وحدة.
     */
    public function show(Unit $unit): JsonResponse
    {
        $unit->load(['baseUnit', 'subUnits']);

        return response()->json($unit);
    }

    /**
     * تحديث وحدة.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return response()->json($unit->fresh('baseUnit'));
    }

    /**
     * حذف وحدة.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        if ($unit->items()->exists()) {
            return response()->json(['message' => 'لا يمكن حذف الوحدة لوجود أصناف مرتبطة بها.'], 422);
        }

        $unit->delete();

        return response()->json(null, 204);
    }
}
