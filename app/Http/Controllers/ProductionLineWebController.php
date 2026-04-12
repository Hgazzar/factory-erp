<?php

namespace App\Http\Controllers;

use App\Models\ProductionLine;
use Illuminate\Http\Request;

class ProductionLineWebController extends Controller
{
    public function index()
    {
        // عرض كل الخطوط مرتبة من الأحدث
        $lines = ProductionLine::latest()->get();
        return view('production-lines.index', compact('lines'));
    }

    public function create()
    {
        return view('production-lines.create');
    }

    public function store(Request $request)
    {
        // التحقق من البيانات بناءً على أعمدة الداتابيز الجديدة
        $request->validate([
            'code'    => 'required|string|max:30|unique:production_lines,code',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        ProductionLine::create($request->all());

        return redirect()->route('production-lines.index')->with('success', 'تم إضافة خط الإنتاج بنجاح');
    }

    public function edit(ProductionLine $productionLine)
    {
        // لاحظ هنا غيرت اسم المتغير لـ line عشان يسهل عليك في الـ Blade
        $line = $productionLine;
        return view('production-lines.edit', compact('line'));
    }

    public function update(Request $request, ProductionLine $productionLine)
    {
        $request->validate([
            'code'    => 'required|string|max:30|unique:production_lines,code,' . $productionLine->id,
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        $productionLine->update($request->all());

        return redirect()->route('production-lines.index')->with('success', 'تم تحديث بيانات الخط بنجاح');
    }

    public function destroy(ProductionLine $productionLine)
    {
        $productionLine->delete();
        return redirect()->route('production-lines.index')->with('success', 'تم حذف الخط بنجاح');
    }
}