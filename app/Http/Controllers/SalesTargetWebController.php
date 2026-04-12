<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesTarget;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesTargetWebController extends Controller
{
    public function index(Request $request): View
    {
        // إعادة حساب سريعة للأهداف النشطة (يمكن استبدالها بـ job لاحقاً)
        SalesTarget::where('status', 'active')->get()->each->recalculateAchievement();

        $query = SalesTarget::query()->orderByDesc('start_date');

        $targets = $query->paginate(20)->withQueryString();

        $all = SalesTarget::all();
        $activeCount = $all->where('status', 'active')->count();
        $achievedCount = $all->filter(fn ($t) => $t->completion_percent >= 100)->count();
        $avgCompletion = $all->isEmpty()
            ? 0
            : round($all->avg(fn ($t) => $t->completion_percent), 1);

        return view('sales.targets.index', [
            'targets' => $targets,
            'activeCount' => $activeCount,
            'achievedCount' => $achievedCount,
            'avgCompletion' => $avgCompletion,
        ]);
    }

    public function create(): View
    {
        $warehouses = Warehouse::active()->orderBy('name_ar')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.targets.create', [
            'warehouses' => $warehouses,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to_type' => ['required', 'in:company,warehouse,customer'],
            'assigned_to_id' => ['nullable', 'integer'],
            'period' => ['required', 'in:monthly,quarterly,yearly,custom'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'threshold_amount' => ['nullable', 'numeric', 'min:0'],
            'stretch_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($data['assigned_to_type'] === 'company') {
            $data['assigned_to_id'] = null;
        }

        $target = SalesTarget::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'target_type' => 'revenue',
            'assigned_to_type' => $data['assigned_to_type'],
            'assigned_to_id' => $data['assigned_to_id'] ?? null,
            'period' => $data['period'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'target_amount' => $data['target_amount'],
            'threshold_amount' => $data['threshold_amount'] ?? null,
            'stretch_amount' => $data['stretch_amount'] ?? null,
            'status' => 'active',
            'created_by' => $request->user()?->id,
        ]);

        $target->recalculateAchievement();

        return redirect()
            ->route('sales.targets.index')
            ->with('success', 'تم إنشاء هدف المبيعات بنجاح.');
    }
}

