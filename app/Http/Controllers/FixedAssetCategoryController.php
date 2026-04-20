<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FixedAssetCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $uid = (int) auth()->id();

        $categories = FixedAssetCategory::query()
            ->with([
                'ledgerAssetAccount:id,code,name_ar',
                'ledgerDepreciationCostAccount:id,code,name_ar',
                'ledgerAccumulatedDepreciationAccount:id,code,name_ar',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('finance.fixed-assets.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        $uid = (int) auth()->id();
        $nextCode = FixedAssetCategory::generateNextCodeForUser($uid);
        $ledgerOptions = $this->ledgerSelectOptions($uid);

        return view('finance.fixed-assets.categories.create', compact('nextCode', 'ledgerOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $this->validatedCategoryPayload($request, $uid);
        $data['user_id'] = $uid;
        $data['code'] = FixedAssetCategory::generateNextCodeForUser($uid);

        FixedAssetCategory::query()->create($data);

        return redirect()
            ->route('finance.fixed-assets.categories.index')
            ->with('success', 'تم إنشاء فئة الأصل وربط حسابات الدليل.');
    }

    public function edit(FixedAssetCategory $category): View
    {
        $uid = (int) auth()->id();
        $ledgerOptions = $this->ledgerSelectOptions($uid, $category);

        return view('finance.fixed-assets.categories.edit', compact('category', 'ledgerOptions'));
    }

    public function update(Request $request, FixedAssetCategory $category): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $this->validatedCategoryPayload($request, $uid);
        $category->update($data);

        return redirect()
            ->route('finance.fixed-assets.categories.index')
            ->with('success', 'تم تحديث فئة الأصل.');
    }

    public function destroy(FixedAssetCategory $category): RedirectResponse
    {
        if (FixedAsset::query()->where('fixed_asset_category_id', $category->id)->exists()) {
            return redirect()
                ->route('finance.fixed-assets.categories.index')
                ->with('error', 'لا يمكن حذف فئة مرتبطة بأصول ثابتة مسجّلة.');
        }

        $category->delete();

        return redirect()
            ->route('finance.fixed-assets.categories.index')
            ->with('success', 'تم حذف فئة الأصل.');
    }

    /**
     * @return array<string, array<int, array{value: int, label: string}>>
     */
    private function ledgerSelectOptions(int $userId, ?FixedAssetCategory $forCategory = null): array
    {
        $extraAssetIds = [];
        $extraExpenseIds = [];
        if ($forCategory) {
            if ($forCategory->ledger_asset_account_id) {
                $extraAssetIds[] = (int) $forCategory->ledger_asset_account_id;
            }
            if ($forCategory->ledger_accumulated_depreciation_account_id) {
                $extraAssetIds[] = (int) $forCategory->ledger_accumulated_depreciation_account_id;
            }
            if ($forCategory->ledger_depreciation_cost_account_id) {
                $extraExpenseIds[] = (int) $forCategory->ledger_depreciation_cost_account_id;
            }
            $extraAssetIds = array_values(array_unique($extraAssetIds));
            $extraExpenseIds = array_values(array_unique($extraExpenseIds));
        }

        $assets = Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_ASSET)
            ->where(function ($q) use ($extraAssetIds) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('parent_id')
                        ->orWhere('allow_direct_posting', true);
                })->where(function ($inner) {
                    $inner->where('is_active', true)
                        ->orWhereNull('is_active');
                });
                if ($extraAssetIds !== []) {
                    $q->orWhereIn('id', $extraAssetIds);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->unique('id')
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();

        $expenses = Account::query()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_EXPENSE)
            ->where(function ($q) use ($extraExpenseIds) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('parent_id')
                        ->orWhere('allow_direct_posting', true);
                })->where(function ($inner) {
                    $inner->where('is_active', true)
                        ->orWhereNull('is_active');
                });
                if ($extraExpenseIds !== []) {
                    $q->orWhereIn('id', $extraExpenseIds);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->unique('id')
            ->map(fn (Account $a) => [
                'value' => $a->id,
                'label' => trim($a->code.' — '.($a->name_ar ?: $a->name_en)),
            ])
            ->values()
            ->all();

        return [
            'assets' => $assets,
            'expenses' => $expenses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCategoryPayload(Request $request, int $userId): array
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'ledger_asset_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('type', Account::TYPE_ASSET);
                }),
            ],
            'ledger_depreciation_cost_account_id' => [
                'required',
                'different:ledger_asset_account_id',
                Rule::exists('accounts', 'id')->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('type', Account::TYPE_EXPENSE);
                }),
            ],
            'ledger_accumulated_depreciation_account_id' => [
                'required',
                'different:ledger_asset_account_id',
                'different:ledger_depreciation_cost_account_id',
                Rule::exists('accounts', 'id')->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('type', Account::TYPE_ASSET);
                }),
            ],
        ]);

        return $data;
    }
}
