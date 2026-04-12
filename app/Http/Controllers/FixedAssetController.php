<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\FixedAsset;
use App\Models\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FixedAssetController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $assets = FixedAsset::query()
            ->with(['categoryRef:id,name_ar,name_en', 'costCenter:id,name,code'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('asset_code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('name_ar', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhereHas('costCenter', function ($costCenterQuery) use ($search) {
                            $costCenterQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('code', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when(in_array($status, ['in_use', 'stopped', 'decommissioned'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('asset_code')
            ->paginate(15)
            ->withQueryString();

        return view('finance.fixed-assets.index', compact('assets', 'search', 'status'));
    }

    public function create(): View
    {
        $categories = ExpenseCategory::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $costCenters = CostCenter::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance.fixed-assets.create', compact('categories', 'costCenters'));
    }

    public function edit(FixedAsset $fixedAsset): View
    {
        $categories = ExpenseCategory::query()
            ->where(function ($query) use ($fixedAsset) {
                $query->where('status', 'active');
                if ($fixedAsset->category_id) {
                    $query->orWhere('id', $fixedAsset->category_id);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $costCenters = CostCenter::query()
            ->where(function ($query) use ($fixedAsset) {
                $query->where('status', 'active');
                if ($fixedAsset->cost_center_id) {
                    $query->orWhere('id', $fixedAsset->cost_center_id);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance.fixed-assets.create', [
            'categories' => $categories,
            'costCenters' => $costCenters,
            'asset' => $fixedAsset,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);

        $data = $request->validate([
            'asset_code' => ['required', 'string', 'max:50', 'unique:fixed_assets,asset_code'],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:expense_categories,id'],
            'cost_center_id' => ['required', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],

            'acquisition_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],

            'depreciation_method' => ['nullable', 'in:straightline,reducing_balance,units_of_production'],
            'useful_life_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'useful_life_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'depreciation_start_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],

            'serial_number' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'warranty_end_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'insurance_document' => ['nullable', 'string', 'max:255'],
            'insurance_end_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
        ]);

        $data['book_value'] = (float) $data['acquisition_cost'];
        $data['status'] = 'in_use';
        $data['category'] = ExpenseCategory::query()->whereKey($data['category_id'])->value('name_ar') ?? 'غير مصنف';

        FixedAsset::query()->create($data);

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with('success', 'تم إضافة الأصل بنجاح.');
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);

        $data = $request->validate([
            'asset_code' => ['required', 'string', 'max:50', 'unique:fixed_assets,asset_code,' . $fixedAsset->id],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:expense_categories,id'],
            'cost_center_id' => ['required', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],

            'acquisition_date' => ['required', 'date'],
            'acquisition_cost' => ['required', 'numeric', 'min:0'],

            'depreciation_method' => ['nullable', 'in:straightline,reducing_balance,units_of_production'],
            'useful_life_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'useful_life_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
            'depreciation_start_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],

            'serial_number' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'warranty_end_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'insurance_document' => ['nullable', 'string', 'max:255'],
            'insurance_end_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
        ]);

        $data['category'] = ExpenseCategory::query()->whereKey($data['category_id'])->value('name_ar') ?? 'غير مصنف';
        if (! isset($fixedAsset->book_value)) {
            $data['book_value'] = (float) $data['acquisition_cost'];
        }

        $fixedAsset->update($data);

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with('success', 'تم تحديث الأصل بنجاح.');
    }

    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        $fixedAsset->delete();

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with('success', 'تم حذف الأصل بنجاح.');
    }
}
