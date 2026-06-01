<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Machine;
use App\Models\ProductionLine;
use App\Models\ProductionShift;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationsShiftController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->input('date'))->toDateString() : now()->toDateString();

        $shifts = Shift::active()->orderBy('code')->get();
        $productionLines = ProductionLine::active()->orderBy('code')->get();
        $machines = Machine::active()->orderBy('code')->get();

        $productionShifts = ProductionShift::with(['shift', 'productionLine', 'machine'])
            ->whereDate('date', $date)
            ->orderBy('shift_id')
            ->orderBy('production_line_id')
            ->orderBy('machine_id')
            ->get();

        return view('operations.shifts.index', compact(
            'date',
            'shifts',
            'productionLines',
            'machines',
            'productionShifts',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'shift_id' => [
                'required',
                Rule::exists('shifts', 'id')->where('user_id', $tenantUserId),
            ],
            'production_line_id' => [
                'nullable',
                Rule::exists('production_lines', 'id')->where('user_id', $tenantUserId),
            ],
            'machine_id' => [
                'nullable',
                Rule::exists('machines', 'id')->where('user_id', $tenantUserId),
            ],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date'],
            'planned_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        ProductionShift::create([
            ...$data,
            'user_id' => $tenantUserId,
            'status' => ProductionShift::STATUS_PLANNED,
            'is_active' => true,
        ]);

        return redirect()
            ->back()
            ->with('success', 'تم إنشاء وردية الإنتاج بنجاح.');
    }

    public function start(ProductionShift $productionShift): RedirectResponse
    {
        $this->resolveOperationsTenantUserId();

        if ($productionShift->status !== ProductionShift::STATUS_IN_PROGRESS) {
            $productionShift->status = ProductionShift::STATUS_IN_PROGRESS;

            if (is_null($productionShift->actual_start_at)) {
                $productionShift->actual_start_at = now();
            }

            $productionShift->save();
        }

        return redirect()
            ->back()
            ->with('success', 'تم بدء الوردية.');
    }

    public function complete(ProductionShift $productionShift): RedirectResponse
    {
        $this->resolveOperationsTenantUserId();

        if ($productionShift->status !== ProductionShift::STATUS_COMPLETED) {
            $productionShift->status = ProductionShift::STATUS_COMPLETED;

            if (is_null($productionShift->actual_start_at)) {
                $productionShift->actual_start_at = now();
            }

            if (is_null($productionShift->actual_end_at)) {
                $productionShift->actual_end_at = now();
            }

            $productionShift->save();
        }

        return redirect()
            ->back()
            ->with('success', 'تم إنهاء الوردية.');
    }
}
