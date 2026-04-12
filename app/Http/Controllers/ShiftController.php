<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Shift::query();

        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->get('per_page', 15);

        return response()->json($query->orderBy('code')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:shifts,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'is_night' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $shift = Shift::create($data);

        return response()->json($shift, 201);
    }

    public function show(Shift $shift): JsonResponse
    {
        return response()->json($shift);
    }

    public function update(Request $request, Shift $shift): JsonResponse
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:30', 'unique:shifts,code,' . $shift->id],
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'is_night' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $shift->update($data);

        return response()->json($shift);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        $shift->delete();

        return response()->json(null, 204);
    }
}

