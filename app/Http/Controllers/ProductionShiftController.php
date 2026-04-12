<?php

namespace App\Http\Controllers;

use App\Models\ProductionShift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductionShift::query()->with(['shift', 'productionLine', 'machine']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date('date'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->date('to_date'));
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->integer('shift_id'));
        }

        if ($request->filled('production_line_id')) {
            $query->where('production_line_id', $request->integer('production_line_id'));
        }

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->integer('machine_id'));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $perPage = (int) $request->get('per_page', 15);

        return response()->json(
            $query->orderBy('date')->orderBy('shift_id')->paginate($perPage)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shift_id' => ['required', 'exists:shifts,id'],
            'production_line_id' => ['nullable', 'exists:production_lines,id'],
            'machine_id' => ['nullable', 'exists:machines,id'],
            'date' => ['required', 'date'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date'],
            'actual_start_at' => ['nullable', 'date'],
            'actual_end_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $productionShift = ProductionShift::create($data);

        return response()->json($productionShift->load(['shift', 'productionLine', 'machine']), 201);
    }

    public function show(ProductionShift $productionShift): JsonResponse
    {
        $productionShift->load(['shift', 'productionLine', 'machine', 'productionLogs.item']);

        return response()->json($productionShift);
    }

    public function update(Request $request, ProductionShift $productionShift): JsonResponse
    {
        $data = $request->validate([
            'shift_id' => ['sometimes', 'exists:shifts,id'],
            'production_line_id' => ['nullable', 'exists:production_lines,id'],
            'machine_id' => ['nullable', 'exists:machines,id'],
            'date' => ['sometimes', 'date'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date'],
            'actual_start_at' => ['nullable', 'date'],
            'actual_end_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $productionShift->update($data);

        return response()->json($productionShift->load(['shift', 'productionLine', 'machine']));
    }

    public function destroy(ProductionShift $productionShift): JsonResponse
    {
        $productionShift->delete();

        return response()->json(null, 204);
    }
}

