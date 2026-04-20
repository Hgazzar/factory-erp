<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Support\AccountingLedgerOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $categories = ItemCategory::query()
            ->with([
                'inventoryAccount:id,code,name_ar',
                'salesIncomeAccount:id,code,name_ar',
                'cogsAccount:id,code,name_ar',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('name_ar', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.item-categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        $uid = (int) auth()->id();
        $invOpts = AccountingLedgerOptions::inventoryAssetAccountsForUser($uid);
        $revOpts = AccountingLedgerOptions::revenueAccountsForUser($uid);
        $cogsOpts = AccountingLedgerOptions::cogsExpenseAccountsForUser($uid);

        return view('inventory.item-categories.create', compact('invOpts', 'revOpts', 'cogsOpts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $this->validatedCategory($request, $uid);
        ItemCategory::query()->create($data);

        return redirect()
            ->route('inventory.item-categories.index')
            ->with('success', 'تم إنشاء فئة المنتجات وربط الحسابات.');
    }

    public function edit(ItemCategory $itemCategory): View
    {
        $uid = (int) auth()->id();
        $invOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::inventoryAssetAccountsForUser($uid),
            $uid,
            (int) $itemCategory->inventory_account_id
        );
        $revOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::revenueAccountsForUser($uid),
            $uid,
            (int) $itemCategory->sales_income_account_id
        );
        $cogsOpts = $this->mergeAccountOption(
            AccountingLedgerOptions::cogsExpenseAccountsForUser($uid),
            $uid,
            (int) $itemCategory->cogs_account_id
        );

        return view('inventory.item-categories.edit', compact('itemCategory', 'invOpts', 'revOpts', 'cogsOpts'));
    }

    public function update(Request $request, ItemCategory $itemCategory): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $this->validatedCategory($request, $uid, $itemCategory->id);
        $itemCategory->update($data);

        return redirect()
            ->route('inventory.item-categories.index')
            ->with('success', 'تم تحديث فئة المنتجات.');
    }

    public function destroy(ItemCategory $itemCategory): RedirectResponse
    {
        if (Item::withoutGlobalScopes()->where('category_id', $itemCategory->id)->exists()) {
            return redirect()
                ->route('inventory.item-categories.index')
                ->with('error', 'لا يمكن حذف فئة مرتبطة بأصناف.');
        }

        $itemCategory->delete();

        return redirect()
            ->route('inventory.item-categories.index')
            ->with('success', 'تم حذف الفئة.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedCategory(Request $request, int $userId, ?int $ignoreId = null): array
    {
        $invIds = collect(AccountingLedgerOptions::inventoryAssetAccountsForUser($userId))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $revIds = collect(AccountingLedgerOptions::revenueAccountsForUser($userId))->pluck('value')->map(fn ($v) => (int) $v)->all();
        $cogsIds = collect(AccountingLedgerOptions::cogsExpenseAccountsForUser($userId))->pluck('value')->map(fn ($v) => (int) $v)->all();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('item_categories', 'name')->ignore($ignoreId),
            ],
            'name_ar' => ['nullable', 'string', 'max:150'],
            'is_active' => ['required', Rule::in([0, 1, '0', '1'])],
            'inventory_account_id' => ['required', 'integer', Rule::in($invIds)],
            'sales_income_account_id' => ['required', 'integer', Rule::in($revIds)],
            'cogs_account_id' => ['required', 'integer', Rule::in($cogsIds)],
        ]);

        $data['is_active'] = (bool) (int) $data['is_active'];

        return $data;
    }

    /**
     * @param  list<array{value: int, label: string}>  $options
     * @return list<array{value: int, label: string}>
     */
    private function mergeAccountOption(array $options, int $userId, int $accountId): array
    {
        $col = collect($options);
        if ($col->contains('value', $accountId)) {
            return $options;
        }
        $acc = Account::query()->where('user_id', $userId)->whereKey($accountId)->first(['id', 'code', 'name_ar', 'name_en']);
        if ($acc) {
            $col->push([
                'value' => $acc->id,
                'label' => trim($acc->code.' — '.($acc->name_ar ?: $acc->name_en)),
            ]);
        }

        return $col->values()->all();
    }
}
