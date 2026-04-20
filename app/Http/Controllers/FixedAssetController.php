<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ExpenseCategory;
use App\Models\FixedAsset;
use App\Models\CostCenter;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function show(FixedAsset $fixedAsset): View
    {
        $fixedAsset->load(['categoryRef:id,code,name_ar,name_en', 'costCenter:id,code,name']);

        return view('finance.fixed-assets.show', ['asset' => $fixedAsset]);
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

        $ledgerAccounts = Account::query()
            ->where('type', Account::TYPE_ASSET)
            ->where(function ($q) {
                $q->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $bankAccounts = BankAccount::query()
            ->where('status', 'active')
            ->whereNotNull('ledger_account_id')
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get(['id', 'bank_name', 'account_number']);

        return view('finance.fixed-assets.create', compact('categories', 'costCenters', 'ledgerAccounts', 'bankAccounts'));
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

        $ledgerAccounts = Account::query()
            ->where('type', Account::TYPE_ASSET)
            ->where(function ($q) use ($fixedAsset) {
                $q->where(function ($inner) {
                    $inner->whereNotNull('parent_id')
                        ->orWhere('allow_direct_posting', true);
                })->where(function ($inner) {
                    $inner->where('is_active', true)
                        ->orWhereNull('is_active');
                });
                if ($fixedAsset->ledger_account_id) {
                    $q->orWhere('id', $fixedAsset->ledger_account_id);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $bankAccounts = BankAccount::query()
            ->where(function ($q) use ($fixedAsset) {
                $q->where(function ($inner) {
                    $inner->where('status', 'active')
                        ->whereNotNull('ledger_account_id');
                });
                if ($fixedAsset->bank_account_id) {
                    $q->orWhere('id', $fixedAsset->bank_account_id);
                }
            })
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get(['id', 'bank_name', 'account_number']);

        return view('finance.fixed-assets.create', [
            'categories' => $categories,
            'costCenters' => $costCenters,
            'ledgerAccounts' => $ledgerAccounts,
            'bankAccounts' => $bankAccounts,
            'asset' => $fixedAsset,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);
        if (! in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)) {
            $request->merge(['bank_account_id' => null]);
        }

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
            'ledger_account_id' => ['required', Rule::exists('accounts', 'id')->where(function ($q) use ($uid) {
                $q->where('user_id', $uid)->where('type', Account::TYPE_ASSET);
            })],
            'payment_method' => ['required', 'in:cash,bank,card,check'],
            'bank_account_id' => [
                Rule::requiredIf(fn () => in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)),
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(function ($q) use ($uid) {
                    $q->where('user_id', $uid)->whereNotNull('ledger_account_id');
                }),
            ],

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
        $data['bank_account_id'] = in_array((string) $data['payment_method'], ['bank', 'check', 'card'], true)
            ? (int) ($data['bank_account_id'] ?? 0)
            : null;

        DB::transaction(function () use ($data, $uid): void {
            $asset = FixedAsset::query()->create($data);
            $entry = $this->createOrSyncAssetJournal($asset, $uid);
            $asset->update(['journal_entry_id' => $entry?->id]);
        });

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with('success', 'تم إضافة الأصل بنجاح.');
    }

    public function update(Request $request, FixedAsset $fixedAsset): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);
        if (! in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)) {
            $request->merge(['bank_account_id' => null]);
        }

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
            'ledger_account_id' => ['required', Rule::exists('accounts', 'id')->where(function ($q) use ($uid) {
                $q->where('user_id', $uid)->where('type', Account::TYPE_ASSET);
            })],
            'payment_method' => ['required', 'in:cash,bank,card,check'],
            'bank_account_id' => [
                Rule::requiredIf(fn () => in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)),
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(function ($q) use ($uid) {
                    $q->where('user_id', $uid)->whereNotNull('ledger_account_id');
                }),
            ],

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
        $data['bank_account_id'] = in_array((string) $data['payment_method'], ['bank', 'check', 'card'], true)
            ? (int) ($data['bank_account_id'] ?? 0)
            : null;

        DB::transaction(function () use ($fixedAsset, $data, $uid): void {
            $fixedAsset->update($data);
            $entry = $this->createOrSyncAssetJournal($fixedAsset->fresh(), $uid);
            $fixedAsset->update(['journal_entry_id' => $entry?->id]);
        });

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with('success', 'تم تحديث الأصل بنجاح.');
    }

    public function destroy(FixedAsset $fixedAsset): RedirectResponse
    {
        DB::transaction(function () use ($fixedAsset): void {
            if ($fixedAsset->journal_entry_id) {
                JournalItem::query()->where('journal_entry_id', (int) $fixedAsset->journal_entry_id)->delete();
                JournalEntry::query()->whereKey((int) $fixedAsset->journal_entry_id)->delete();
            }
            $fixedAsset->delete();
        });

        return redirect()
            ->route('finance.fixed-assets.index')
            ->with('success', 'تم حذف الأصل بنجاح.');
    }

    private function createOrSyncAssetJournal(FixedAsset $asset, int $userId): ?JournalEntry
    {
        $debitAccountId = (int) ($asset->ledger_account_id ?? 0);
        if ($debitAccountId < 1) {
            return null;
        }

        $creditAccount = $this->resolveAssetCreditAccount($asset);
        $total = (float) ($asset->acquisition_cost ?? 0);
        if ($total <= 0) {
            return null;
        }

        $entry = $asset->journal_entry_id
            ? JournalEntry::query()->find((int) $asset->journal_entry_id)
            : null;

        if (! $entry) {
            $entry = JournalEntry::query()->create([
                'user_id' => $userId,
                'date' => $asset->acquisition_date,
                'reference' => 'FA',
                'description' => 'تسجيل أصل ثابت #'.$asset->asset_code,
                'total' => $total,
            ]);
        } else {
            $entry->update([
                'date' => $asset->acquisition_date,
                'description' => 'تسجيل أصل ثابت #'.$asset->asset_code,
                'total' => $total,
            ]);
            JournalItem::query()->where('journal_entry_id', $entry->id)->delete();
        }

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $debitAccountId,
            'description' => 'تحميل قيمة الأصل',
            'debit' => $total,
            'credit' => 0,
        ]);

        JournalItem::query()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'description' => 'مصدر تمويل الأصل',
            'debit' => 0,
            'credit' => $total,
        ]);

        return $entry;
    }

    private function resolveAssetCreditAccount(FixedAsset $asset): Account
    {
        $method = (string) ($asset->payment_method ?? 'cash');
        if (in_array($method, ['bank', 'check', 'card'], true) && $asset->bank_account_id) {
            $bank = BankAccount::query()->whereKey($asset->bank_account_id)->first();
            if ($bank && $bank->ledger_account_id) {
                $account = Account::query()->whereKey((int) $bank->ledger_account_id)->first();
                if ($account) {
                    return $account;
                }
            }
        }

        return DefaultLedgerAccounts::paymentSourceAsset($method);
    }
}
