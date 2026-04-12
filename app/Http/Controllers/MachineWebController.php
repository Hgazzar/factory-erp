<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\ProductionLine; // ضفنا ده عشان نختار خط الإنتاج
use Illuminate\Http\Request;

class MachineWebController extends Controller
{
    public function index()
    {
        // استخدام with عشان يحمل بيانات خط الإنتاج مع الماكينة (Performance)
        $machines = Machine::with('productionLine')->get();
        return view('machines.index', compact('machines'));
    }

    public function create()
    {
        // بنجيب خطوط الإنتاج عشان تختار منها في الـ Dropdown
        $productionLines = ProductionLine::all();
        return view('machines.create', compact('productionLines'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|max:30|unique:machines,code',
            'name_ar' => 'required|string|max:255',
            'production_line_id' => 'nullable|exists:production_lines,id',
            'status' => 'nullable|in:active,maintenance,inactive', // ضيف السطر ده هنا
        ]);
    
        Machine::create($request->all());
        return redirect()->route('machines.index')->with('success', 'تم إضافة الماكينة بنجاح');
    }

    public function edit(Machine $machine)
    {
        $productionLines = ProductionLine::all();
        return view('machines.edit', compact('machine', 'productionLines'));
    }

public function update(Request $request, Machine $machine)
{
    $request->validate([
        'code' => 'required|max:30|unique:machines,code,' . $machine->id,
        'name_ar' => 'required|string|max:255',
        'production_line_id' => 'nullable|exists:production_lines,id',
        'status' => 'nullable|in:active,maintenance,inactive', // وضيفه هنا كمان
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