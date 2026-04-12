<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\ProductionLine;
use App\Models\ProductionShift;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationsShiftController extends Controller
{
    public function index(Request $request): View
    {
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
            'productionShifts'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'shift_id' => ['required', 'exists:shifts,id'],
            'production_line_id' => ['nullable', 'exists:production_lines,id'],
            'machine_id' => ['nullable', 'exists:machines,id'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date'],
            'planned_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['status'] ??= ProductionShift::STATUS_PLANNED;
        $data['is_active'] = true;

        ProductionShift::create($data);

        return redirect()
            ->back()
            ->with('success', 'تم إنشاء وردية الإنتاج بنجاح.');
    }

    public function start(ProductionShift $productionShift): RedirectResponse
    {
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

