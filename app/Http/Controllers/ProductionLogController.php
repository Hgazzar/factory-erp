<?php

namespace App\Http\Controllers;

use App\Models\ProductionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductionLog::query()->with(['productionShift.shift', 'productionShift.productionLine', 'productionShift.machine', 'item']);

        if ($request->filled('production_shift_id')) {
            $query->where('production_shift_id', $request->integer('production_shift_id'));
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->integer('item_id'));
        }

        if ($request->filled('from_datetime')) {
            $query->where('logged_at', '>=', $request->date('from_datetime'));
        }

        if ($request->filled('to_datetime')) {
            $query->where('logged_at', '<=', $request->date('to_datetime'));
        }

        $perPage = (int) $request->get('per_page', 15);

        return response()->json(
            $query->orderBy('logged_at')->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'production_shift_id' => ['required', 'exists:production_shifts,id'],
            'item_id' => ['required', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'logged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($data['logged_at'])) {
            $data['logged_at'] = now();
        }

        $log = ProductionLog::create($data);

        return response()->json($log->load(['productionShift.shift', 'item']), 201);
    }

    public function show(ProductionLog $productionLog): JsonResponse
    {
        $productionLog->load(['productionShift.shift', 'productionShift.productionLine', 'productionShift.machine', 'item']);

        return response()->json($productionLog);
    }

    public function update(Request $request, ProductionLog $productionLog): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['sometimes', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'logged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $productionLog->update($data);

        return response()->json($productionLog->load(['productionShift.shift', 'item']));
    }

    public function destroy(ProductionLog $productionLog): JsonResponse
    {
        $productionLog->delete();

        return response()->json(null, 204);
    }
}

