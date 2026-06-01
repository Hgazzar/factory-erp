<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Machine;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MachineWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index()
    {
        $machines = Machine::with('productionLine')->orderBy('code')->get();

        return view('machines.index', compact('machines'));
    }

    public function create()
    {
        $productionLines = ProductionLine::active()->orderBy('code')->get();

        return view('machines.create', compact('productionLines'));
    }

    public function store(Request $request)
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $request->validate([
            'code' => [
                'required',
                'max:30',
                Rule::unique('machines', 'code')->where('user_id', $tenantUserId),
            ],
            'name_ar' => 'required|string|max:255',
            'production_line_id' => [
                'nullable',
                Rule::exists('production_lines', 'id')->where('user_id', $tenantUserId),
            ],
            'status' => 'nullable|in:active,maintenance,inactive',
        ]);

        Machine::create([
            ...$request->only(['code', 'name_ar', 'name_en', 'production_line_id', 'description', 'status', 'is_active', 'depreciation_rate_per_unit']),
            'user_id' => $tenantUserId,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('machines.index')->with('success', 'تم إضافة الماكينة بنجاح');
    }

    public function edit(Machine $machine)
    {
        $productionLines = ProductionLine::active()->orderBy('code')->get();

        return view('machines.edit', compact('machine', 'productionLines'));
    }

    public function update(Request $request, Machine $machine)
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $request->validate([
            'code' => [
                'required',
                'max:30',
                Rule::unique('machines', 'code')->where('user_id', $tenantUserId)->ignore($machine->id),
            ],
            'name_ar' => 'required|string|max:255',
            'production_line_id' => [
                'nullable',
                Rule::exists('production_lines', 'id')->where('user_id', $tenantUserId),
            ],
            'status' => 'nullable|in:active,maintenance,inactive',
        ]);

        $machine->update($request->all());

        return redirect()->route('machines.index')->with('success', 'تم تحديث البيانات بنجاح');
    }

    public function destroy(Machine $machine)
    {
        $machine->delete();

        return redirect()->route('machines.index')->with('success', 'تم حذف الماكينة');
    }
}
