<?php

namespace App\Http\Controllers;

use App\Models\CommissionRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionRuleWebController extends Controller
{
    public function index(): View
    {
        $rules = CommissionRule::orderBy('priority')->orderByDesc('valid_from')->paginate(20);

        return view('sales.commissions.rules.index', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:percentage'],
            'basis' => ['required', 'in:revenue'],
            'rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'priority' => ['required', 'integer', 'min:0'],
        ]);

        CommissionRule::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'basis' => $data['basis'],
            'rate_percent' => $data['rate_percent'],
            'min_amount' => $data['min_amount'] ?? null,
            'max_amount' => $data['max_amount'] ?? null,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?? null,
            'priority' => (int) $data['priority'],
            'status' => 'active',
        ]);

        return redirect()
            ->route('sales.commissions.rules.index')
            ->with('success', 'تم حفظ قاعدة العمولة بنجاح.');
    }
}
