<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseWebController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $warehouses = Warehouse::query()
            ->when($search !== '', fn ($q) => $q->where('code', 'like', "%{$search}%")
                ->orWhere('name_ar', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%"))
            ->orderBy('code')
            ->get();
        $total = Warehouse::count();
        $activeCount = Warehouse::where('is_active', true)->count();
        $defaultWarehouse = Warehouse::default()->first() ?? Warehouse::where('is_active', true)->orderBy('code')->first();
        return view('warehouses.index', compact('warehouses', 'total', 'activeCount', 'defaultWarehouse', 'search'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $userId = (int) auth()->id();

        $request->validate([
            'code' => [
                'required',
                'max:30',
                Rule::unique('warehouses', 'code')->where('user_id', $userId),
            ],
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'manager' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'map_location' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
        ]);

        $warehouse = Warehouse::create([
            'user_id' => $userId,
            'code' => $request->code,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'address' => $request->address,
            'city' => $request->city,
            'manager' => $request->manager,
            'phone' => $request->phone,
            'map_location' => $request->map_location,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'is_default' => $request->has('is_default'),
        ]);

        if ($warehouse->is_default) {
            Warehouse::where('user_id', $warehouse->user_id)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('warehouses.index')->with('success', 'تم إضافة المستودع بنجاح');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $userId = (int) auth()->id();

        $request->validate([
            'code' => [
                'required',
                'max:30',
                Rule::unique('warehouses', 'code')
                    ->where('user_id', $userId)
                    ->ignore($warehouse->id),
            ],
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'manager' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'map_location' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
        ]);

        $warehouse->update([
            'user_id' => $userId,
            'code' => $request->code,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'address' => $request->address,
            'city' => $request->city,
            'manager' => $request->manager,
            'phone' => $request->phone,
            'map_location' => $request->map_location,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'is_default' => $request->has('is_default'),
        ]);

        if ($warehouse->is_default) {
            Warehouse::where('user_id', $warehouse->user_id)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('warehouses.index')->with('success', 'تم تحديث بيانات المستودع بنجاح');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'تم حذف المخزن');
    }
}