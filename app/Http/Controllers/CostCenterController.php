<?php

namespace App\Http\Controllers;

use App\Models\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CostCenterController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $centers = CostCenter::query()
            ->withSum(['expenses as expenses_amount_total' => function ($query) {
                $query->where('type', 'expense');
            }], 'amount')
            ->withSum(['expenses as expenses_tax_total' => function ($query) {
                $query->where('type', 'expense');
            }], 'tax_amount')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('branch', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('finance.cost-centers.index', compact('centers', 'search'));
    }

    public function create(): View
    {
        $parents = CostCenter::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $nextCode = $this->nextCode();

        return view('finance.cost-centers.create', compact('parents', 'nextCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cost_centers', 'code')->where('user_id', $uid),
            ],
            'name' => ['required', 'string', 'max:255'],
            'branch' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'annual_budget' => ['nullable', 'numeric', 'min:0'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);

        $data['user_id'] = $uid;
        $data['annual_budget'] = (float) ($data['annual_budget'] ?? 0);
        $data['monthly_budget'] = (float) ($data['monthly_budget'] ?? 0);
        $data['status'] = $request->boolean('is_active') ? 'active' : 'inactive';

        CostCenter::query()->create($data);

        return redirect()
            ->route('finance.cost-centers.index')
            ->with('success', 'تم إنشاء مركز التكلفة بنجاح.');
    }

    public function show(CostCenter $costCenter): RedirectResponse
    {
        return redirect()->route('finance.cost-centers.edit', $costCenter);
    }

    public function edit(CostCenter $costCenter): View
    {
        $parents = CostCenter::query()
            ->where('status', 'active')
            ->where('id', '!=', $costCenter->id)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance.cost-centers.edit', ['center' => $costCenter, 'parents' => $parents]);
    }

    public function update(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cost_centers', 'code')->where('user_id', $uid)->ignore($costCenter->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'branch' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'annual_budget' => ['nullable', 'numeric', 'min:0'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);

        $data['annual_budget'] = (float) ($data['annual_budget'] ?? 0);
        $data['monthly_budget'] = (float) ($data['monthly_budget'] ?? 0);
        $data['status'] = $request->boolean('is_active') ? 'active' : 'inactive';

        $costCenter->update($data);

        return redirect()
            ->route('finance.cost-centers.index')
            ->with('success', 'تم تحديث مركز التكلفة بنجاح.');
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        $costCenter->delete();

        return redirect()
            ->route('finance.cost-centers.index')
            ->with('success', 'تم حذف مركز التكلفة.');
    }

    private function nextCode(): string
    {
        $maxCode = (string) CostCenter::query()->max('code');
        if (! preg_match('/^CC-(\d+)$/', $maxCode, $matches)) {
            return 'CC-001';
        }

        $next = (int) $matches[1] + 1;

        return 'CC-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
