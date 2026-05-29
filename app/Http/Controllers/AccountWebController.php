<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\AuditTrail;
use App\Models\BankAccount;
use App\Models\CompanySetting;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\ItemCategory;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\PaymentMethodAccount;
use App\Models\TaxRate;
use App\Services\UniversalImportService;
use App\Support\DefaultLedgerAccounts;
use App\Support\ErpFilamentNotification;
use App\Support\ErpRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class AccountWebController extends Controller
{
    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'Code',
            'Name',
            'Name Ar',
            'Account Type',
            'Parent Account Code',
            'Is Active',
            'Allow Direct Posting',
            'Opening Balance',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="accounts-import-template.csv"',
        ]);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_ACCOUNTS);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $created = (int) ($summary['created'] ?? 0);
        $updated = (int) ($summary['updated'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);
        $total = $created + $updated + $failed;
        ErpFilamentNotification::successImport(
            'تم استيراد الحسابات بنجاح',
            "إجمالي الصفوف المعالجة: {$total} — إضافة: {$created} — تحديث: {$updated} — فشل: {$failed}"
        );

        return redirect()
            ->route('finance.accounts.index')
            ->with('import_result', $summary);
    }

    public function create(): View
    {
        $parentAccounts = Account::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'parent_id']);

        $uid = (int) (auth()->id() ?? 1);
        $parentId = old('parent_id') ? (int) old('parent_id') : null;
        if ($parentId && ! Account::query()->whereKey($parentId)->exists()) {
            $parentId = null;
        }
        $suggestedCode = Account::generateNextNumericCodeForUser($uid, $parentId);

        return view('finance.accounts.create', [
            'parentAccounts' => $parentAccounts,
            'suggestedCode' => $suggestedCode,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('user_id', auth()->id())],
            'type' => ['required', 'in:asset,liability,equity,expense,revenue'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_bank' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $uid = (int) (auth()->id() ?? 1);
        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $data['user_id'] = $uid;
        $data['code'] = Account::generateNextNumericCodeForUser($uid, $parentId);
        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['current_balance'] = $data['opening_balance'];
        $data['is_bank'] = $request->boolean('is_bank');
        $data['is_active'] = $request->boolean('is_active');

        Account::create($data);

        return redirect()
            ->route('finance.accounts.index')
            ->with('success', 'تم إنشاء الحساب بنجاح.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $uid = (int) (auth()->id() ?? 0);

        $data = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounts', 'code')
                    ->where('user_id', $uid)
                    ->ignore($account->id),
            ],
        ], [
            'code.unique' => 'رمز الحساب مستخدم مسبقاً ضمن دليل حساباتك. اختر رمزاً آخر.',
        ], [
            'name_ar' => 'اسم الحساب',
            'code' => 'رمز الحساب',
        ]);

        $data['code'] = trim($data['code']);
        $data['name_ar'] = trim($data['name_ar']);

        $account->update($data);

        return redirect()
            ->route('finance.accounts.index', $request->only(['search', 'type']))
            ->with('success', 'تم تحديث بيانات الحساب بنجاح.');
    }

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        if (! ErpRoles::canDeleteExpenseDraft($request->user())) {
            abort(403);
        }

        $blocked = $this->accountStructuralDeletionBlocked($account);
        if ($blocked !== null) {
            $msg = $blocked;
            if (ErpRoles::isSuperAdmin($request->user())) {
                $msg .= ' كسوبر أدمن يمكنك استخدام «تطهير الحساب» لإعادة توجيه الربط ثم الإكمال.';
            }

            return redirect()
                ->route('finance.accounts.index', $request->only(['search', 'type']))
                ->with('error', $msg);
        }

        if (JournalItem::query()->where('account_id', $account->id)->exists()) {
            if (ErpRoles::isSuperAdmin($request->user())) {
                return redirect()
                    ->route('finance.accounts.index', $request->only(['search', 'type']))
                    ->with('error', 'لا يمكن حذف الحساب طالما توجد قيود؛ استخدم «تطهير الحساب» لإزالة القيود المتعلقة ثم الحذف النهائي.');
            }

            return redirect()
                ->route('finance.accounts.index', $request->only(['search', 'type']))
                ->with('error', 'لا يمكن حذف هذا الحساب لوجود حركات مالية مسجّلة عليه.');
        }

        DB::transaction(function () use ($account): void {
            $ownerId = (int) $account->user_id;
            $accountPk = (int) $account->id;
            $code = (string) ($account->code ?? '');
            $nameAr = (string) ($account->name_ar ?? '');

            AuditLog::logFinancialControl(
                'account_delete',
                $ownerId,
                Account::class,
                $accountPk,
                [
                    'description' => 'حذف حساب من دليل الحسابات: '.$code.($nameAr !== '' ? ' — '.$nameAr : ''),
                    'original_user_id' => $ownerId,
                    'code' => $code,
                    'name_ar' => $nameAr !== '' ? $nameAr : null,
                    'account_type' => $account->type,
                ]
            );

            AuditTrail::log('delete', 'accounts', $accountPk, [
                'code' => $code,
                'name_ar' => $nameAr !== '' ? $nameAr : null,
                'type' => $account->type,
            ], null);

            $account->delete();
        });

        return redirect()
            ->route('finance.accounts.index', $request->only(['search', 'type']))
            ->with('success', 'تم حذف الحساب بنجاح.');
    }

    /**
     * إزالة القيود التي تمس الحساب (والفروع تحته) ثم الحذف — سوبر أدمن فقط.
     * يُعالَج الشجر من الأسفل: تطهير كل ورقة ثم الأب، حتى لا يعوق شرط «حسابات فرعية» التطهير الجذري.
     */
    public function purge(Request $request, Account $account): RedirectResponse
    {
        if (! ErpRoles::isSuperAdmin($request->user())) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($account): void {
                $this->purgeAccountSubtree($account);
            });
        } catch (RuntimeException $e) {
            return redirect()
                ->route('finance.accounts.index', $request->only(['search', 'type']))
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('finance.accounts.index', $request->only(['search', 'type']))
            ->with('success', 'تم تطهير الحساب وجميع الحسابات الفرعية التابعة له وحذفها بعد إزالة القيود المرتبطة.');
    }

    /**
     * تطهير وحذف الشجرة التحتية: الأبناء أولاً ثم الجذر الحالي.
     */
    private function purgeAccountSubtree(Account $account): void
    {
        $children = Account::query()
            ->where('parent_id', $account->id)
            ->orderBy('code')
            ->get();

        foreach ($children as $child) {
            $this->purgeAccountSubtree($child);
        }

        $fresh = Account::query()->whereKey($account->id)->first();
        if (! $fresh) {
            return;
        }

        $this->reassignPaymentMethodsAwayFromPurgedAccount($fresh);
        $this->reassignBankAccountsAwayFromPurgedAccount($fresh);
        $this->reassignFixedAssetCategoriesAwayFromPurgedAccount($fresh);
        $this->nullFixedAssetsLedgerPointingToPurgedAccount($fresh);

        $blocked = $this->accountStructuralDeletionBlocked($fresh);
        if ($blocked !== null) {
            throw new RuntimeException($blocked);
        }

        $this->purgeLeafAccountRemoveJournalsAndDelete($fresh);
    }

    /**
     * تطهير قيود حساب ورقة (بعد التأكد أنه لا يبقى له أبناء) ثم حذف السجل.
     */
    private function purgeLeafAccountRemoveJournalsAndDelete(Account $account): void
    {
        $tenantUid = (int) $account->user_id;
        $accountPk = (int) $account->id;
        $accountCode = (string) ($account->code ?? '');

        $entryIds = JournalItem::query()
            ->where('account_id', $account->id)
            ->pluck('journal_entry_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if ($entryIds !== []) {
            $payments = Payment::withoutGlobalScopes()
                ->where('user_id', $tenantUid)
                ->where('type', 'expense')
                ->whereIn('journal_entry_id', $entryIds)
                ->get();

            foreach ($payments as $expense) {
                if ($expense->fixed_asset_id) {
                    FixedAsset::query()
                        ->whereKey((int) $expense->fixed_asset_id)
                        ->where('source_payment_id', $expense->id)
                        ->delete();
                }
            }

            Payment::withoutGlobalScopes()
                ->where('user_id', $tenantUid)
                ->where('type', 'expense')
                ->whereIn('journal_entry_id', $entryIds)
                ->update([
                    'journal_entry_id' => null,
                    'status' => 'draft',
                    'fixed_asset_id' => null,
                ]);

            JournalEntry::withoutGlobalScopes()
                ->where('user_id', $tenantUid)
                ->whereIn('id', $entryIds)
                ->delete();
        }

        AuditLog::logFinancialControl(
            'account_purge_and_delete',
            $tenantUid,
            Account::class,
            $accountPk,
            [
                'description' => 'تطهير وحذف حساب من الدليل: '.$accountCode.' (إزالة '.count($entryIds).' قيد/قيود)',
                'original_user_id' => $tenantUid,
                'account_code' => $accountCode,
                'name_ar' => $account->name_ar,
                'account_type' => $account->type,
                'journal_entries_removed' => count($entryIds),
            ]
        );

        AuditTrail::log('delete', 'accounts', $accountPk, [
            'code' => $accountCode,
            'name_ar' => $account->name_ar,
            'type' => $account->type,
            'purge_journal_entries_removed' => count($entryIds),
        ], null);

        $account->delete();
    }

    /**
     * أثناء التطهير فقط: تحريك ربط وسائل الدفع عن الحساب المراد حذفه حتى لا يمنع FK التطهير.
     */
    private function reassignPaymentMethodsAwayFromPurgedAccount(Account $account): void
    {
        $uid = (int) $account->user_id;
        $excludeId = (int) $account->id;

        $methodLabels = [
            PaymentMethodAccount::KEY_CASH => 'نقدي',
            PaymentMethodAccount::KEY_TRANSFER => 'تحويل بنكي',
            PaymentMethodAccount::KEY_CARD => 'بطاقة / شبكة',
        ];

        $rows = PaymentMethodAccount::withoutGlobalScopes()
            ->where('user_id', $uid)
            ->where('ledger_account_id', $excludeId)
            ->get();

        foreach ($rows as $row) {
            $fallback = $this->fallbackLedgerAccountForPaymentMethodAfterPurge($uid, (string) $row->method_key, $excludeId);
            if ($fallback === null) {
                $label = $methodLabels[(string) $row->method_key] ?? (string) $row->method_key;
                throw new RuntimeException(
                    'تعذّر إكمال التطهير: وسيلة الدفع «'.$label.'» تشير إلى هذا الحساب ولا يوجد حساب بديل صالح في الدليل. عدّل «ربط وسائل الدفع بالحسابات» ثم أعد المحاولة.'
                );
            }

            PaymentMethodAccount::withoutGlobalScopes()
                ->whereKey($row->id)
                ->update(['ledger_account_id' => $fallback]);
        }
    }

    private function fallbackLedgerAccountForPaymentMethodAfterPurge(int $userId, string $methodKey, int $excludingAccountId): ?int
    {
        $preferredCode = \in_array($methodKey, [PaymentMethodAccount::KEY_TRANSFER, PaymentMethodAccount::KEY_CARD], true)
            ? '1020'
            : '1010';

        $preferredId = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', $preferredCode)
            ->value('id');

        if ($preferredId && (int) $preferredId !== $excludingAccountId) {
            return (int) $preferredId;
        }

        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_ASSET)
            ->whereKeyNot($excludingAccountId)
            ->orderBy('code')
            ->value('id');
    }

    /**
     * أثناء التطهير: نقل ربط الحسابات البنكية عن حساب الدليل المحذوف حتى لا يمنع القيد FK والتحقق الهيكلي.
     */
    private function reassignBankAccountsAwayFromPurgedAccount(Account $account): void
    {
        $uid = (int) $account->user_id;
        $excludeId = (int) $account->id;

        $rows = BankAccount::withoutGlobalScopes()
            ->where('user_id', $uid)
            ->where('ledger_account_id', $excludeId)
            ->get();

        foreach ($rows as $row) {
            $fallback = $this->fallbackLedgerAccountForBankAfterPurge($uid, $excludeId);
            if ($fallback === null) {
                throw new RuntimeException(
                    'تعذّر إكمال التطهير: حساب بنكي يشير إلى هذا الحساب في الدليل ولا يوجد حساب بديل صالح. عدّل ربط الحساب البنكي من الإعدادات أو أضف حساباً بديلاً ثم أعد المحاولة.'
                );
            }

            BankAccount::withoutGlobalScopes()
                ->whereKey($row->id)
                ->update(['ledger_account_id' => $fallback]);
        }
    }

    private function fallbackLedgerAccountForBankAfterPurge(int $userId, int $excludingAccountId): ?int
    {
        $preferredId = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('code', '1020')
            ->value('id');

        if ($preferredId && (int) $preferredId !== $excludingAccountId) {
            return (int) $preferredId;
        }

        return Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('type', Account::TYPE_ASSET)
            ->whereKeyNot($excludingAccountId)
            ->orderBy('code')
            ->value('id');
    }

    /**
     * أثناء التطهير: استبدال مراجع دليل الحساب في فئات الأصول الثابتة قبل الحذف (restrictOnDelete على الأعمدة الثلاثة).
     */
    private function reassignFixedAssetCategoriesAwayFromPurgedAccount(Account $account): void
    {
        $uid = (int) $account->user_id;
        $excludeId = (int) $account->id;

        $cats = FixedAssetCategory::withoutGlobalScopes()
            ->where('user_id', $uid)
            ->where(function ($q) use ($excludeId) {
                $q->where('ledger_asset_account_id', $excludeId)
                    ->orWhere('ledger_depreciation_cost_account_id', $excludeId)
                    ->orWhere('ledger_accumulated_depreciation_account_id', $excludeId);
            })
            ->get();

        foreach ($cats as $cat) {
            $updates = [];

            if ((int) $cat->ledger_asset_account_id === $excludeId) {
                $fb = $this->fallbackLedgerForFixedAssetCategory($uid, $excludeId, 'asset');
                if ($fb === null) {
                    throw new RuntimeException(
                        'تعذّر إكمال التطهير: فئة أصول ثابتة تستخدم هذا الحساب كحساب أصل ولا يوجد حساب أصول بديل. أضف حساباً بديلاً ثم أعد المحاولة.'
                    );
                }
                $updates['ledger_asset_account_id'] = $fb;
            }

            if ((int) $cat->ledger_depreciation_cost_account_id === $excludeId) {
                $fb = $this->fallbackLedgerForFixedAssetCategory($uid, $excludeId, 'depreciation_expense');
                if ($fb === null) {
                    throw new RuntimeException(
                        'تعذّر إكمال التطهير: فئة أصول ثابتة تستخدم هذا الحساب كمصروف إهلاك ولا يوجد حساب مصروف بديل. أضف حساباً بديلاً ثم أعد المحاولة.'
                    );
                }
                $updates['ledger_depreciation_cost_account_id'] = $fb;
            }

            if ((int) $cat->ledger_accumulated_depreciation_account_id === $excludeId) {
                $fb = $this->fallbackLedgerForFixedAssetCategory($uid, $excludeId, 'accumulated_depreciation');
                if ($fb === null) {
                    throw new RuntimeException(
                        'تعذّر إكمال التطهير: فئة أصول ثابتة تستخدم هذا الحساب كمجمع إهلاك ولا يوجد حساب أصول بديل. أضف حساباً بديلاً ثم أعد المحاولة.'
                    );
                }
                $updates['ledger_accumulated_depreciation_account_id'] = $fb;
            }

            if ($updates !== []) {
                FixedAssetCategory::withoutGlobalScopes()
                    ->whereKey($cat->id)
                    ->update($updates);
            }
        }
    }

    /** أي أصول ثابتة ما زالت تشير إلى حساب الدليل المحذوف تُفرَّغ المرجع (nullable FK). */
    private function nullFixedAssetsLedgerPointingToPurgedAccount(Account $account): void
    {
        FixedAsset::query()
            ->where('ledger_account_id', $account->id)
            ->update(['ledger_account_id' => null]);
    }

    /**
     * @param  'asset'|'depreciation_expense'|'accumulated_depreciation'  $kind
     */
    private function fallbackLedgerForFixedAssetCategory(int $userId, int $excludingAccountId, string $kind): ?int
    {
        $preferred = match ($kind) {
            'asset' => DefaultLedgerAccounts::fixedAssetPostingAccount($userId),
            'depreciation_expense' => DefaultLedgerAccounts::depreciationExpenseAccount($userId),
            'accumulated_depreciation' => DefaultLedgerAccounts::accumulatedDepreciationAccount($userId),
        };

        if ((int) $preferred->id !== $excludingAccountId) {
            return (int) $preferred->id;
        }

        $accountType = \in_array($kind, ['asset', 'accumulated_depreciation'], true)
            ? Account::TYPE_ASSET
            : Account::TYPE_EXPENSE;

        $fallbackId = Account::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('type', $accountType)
            ->whereKeyNot($excludingAccountId)
            ->orderBy('code')
            ->value('id');

        return $fallbackId !== null ? (int) $fallbackId : null;
    }

    private function accountStructuralDeletionBlocked(Account $account): ?string
    {
        if ($account->children()->exists()) {
            return 'لا يمكن حذف هذا الحساب لوجود حسابات فرعية.';
        }

        if (BankAccount::withoutGlobalScopes()
            ->where('ledger_account_id', $account->id)
            ->where('user_id', $account->user_id)
            ->where('status', 'active')
            ->exists()) {
            return 'لا يمكن حذف هذا الحساب لأنه مرتبط بحساب بنكي نشط.';
        }

        if (FixedAssetCategory::withoutGlobalScopes()
            ->where('user_id', $account->user_id)
            ->where(function ($q) use ($account) {
                $q->where('ledger_asset_account_id', $account->id)
                    ->orWhere('ledger_depreciation_cost_account_id', $account->id)
                    ->orWhere('ledger_accumulated_depreciation_account_id', $account->id);
            })
            ->exists()) {
            return 'لا يمكن حذف هذا الحساب لأنه مرتبط بفئة أصول ثابتة.';
        }

        if (TaxRate::withoutGlobalScopes()
            ->where('user_id', $account->user_id)
            ->where('ledger_account_id', $account->id)
            ->exists()) {
            return 'لا يمكن حذف هذا الحساب لأنه مرتبط بضريبة في إعدادات الضرائب.';
        }

        if (PaymentMethodAccount::withoutGlobalScopes()
            ->where('user_id', $account->user_id)
            ->where('ledger_account_id', $account->id)
            ->exists()) {
            return 'لا يمكن حذف هذا الحساب لأنه مرتبط بوسيلة دفع.';
        }

        if (ItemCategory::query()
            ->where(function ($q) use ($account) {
                $q->where('inventory_account_id', $account->id)
                    ->orWhere('sales_income_account_id', $account->id)
                    ->orWhere('cogs_account_id', $account->id);
            })
            ->exists()) {
            return 'لا يمكن حذف هذا الحساب لأنه مرتبط بفئة منتجات.';
        }

        if (CompanySetting::query()
            ->where('user_id', $account->user_id)
            ->where(function ($q) use ($account) {
                $q->where('default_receivable_account_id', $account->id)
                    ->orWhere('default_payable_account_id', $account->id)
                    ->orWhere('purchase_discount_ledger_account_id', $account->id)
                    ->orWhere('sales_allowed_discount_ledger_account_id', $account->id);
            })
            ->exists()) {
            return 'لا يمكن حذف هذا الحساب لأنه مستخدم في إعدادات المنشأة العامة.';
        }

        return null;
    }

    public function toggleActive(Account $account): RedirectResponse
    {
        $account->update(['is_active' => ! $account->is_active]);

        return back();
    }

    public function index(Request $request): View|Response
    {
        $query = Account::query()->with('childrenRecursive');

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->get('export') === 'csv') {
            $accounts = Account::query()
                ->when($request->filled('search'), function ($q) use ($request) {
                    $term = $request->search;
                    $q->where(function ($q) use ($term) {
                        $q->where('name_ar', 'like', "%{$term}%")
                            ->orWhere('name_en', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%");
                    });
                })
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->orderBy('code')
                ->limit(5000)
                ->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "الكود,الاسم,النوع,الرصيد الافتتاحي,نشط\n";
            $typeLabels = ['asset' => 'أصول', 'liability' => 'خصوم', 'equity' => 'حقوق ملكية', 'expense' => 'مصروف', 'revenue' => 'إيراد'];
            foreach ($accounts as $a) {
                $csv .= '"'.str_replace('"', '""', $a->code ?? '').'","'.str_replace('"', '""', $a->name_ar ?? $a->name_en ?? '').'","'.($typeLabels[$a->type] ?? $a->type).'",'.(float) ($a->opening_balance ?? 0).','.($a->is_active ? 'نعم' : 'لا')."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="accounts-'.date('Y-m-d').'.csv"',
            ]);
        }

        $rootAccounts = (clone $query)
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        $accountIds = [];
        $collectIds = function ($accounts) use (&$collectIds, &$accountIds): void {
            foreach ($accounts as $node) {
                $accountIds[] = (int) $node->id;
                if ($node->relationLoaded('childrenRecursive') && $node->childrenRecursive) {
                    $collectIds($node->childrenRecursive);
                }
            }
        };
        $collectIds($rootAccounts);

        $movementsByAccount = DB::table('journal_items')
            ->selectRaw('account_id, SUM(debit - credit) AS movement')
            ->whereIn('account_id', $accountIds ?: [0])
            ->groupBy('account_id')
            ->pluck('movement', 'account_id')
            ->map(fn ($v) => (float) $v);

        $openingsByAccount = Account::query()
            ->whereIn('id', $accountIds ?: [0])
            ->pluck('opening_balance', 'id')
            ->map(fn ($v) => (float) $v);

        $balancesByAccount = [];
        $sumDebit = 0.0;
        $sumCredit = 0.0;
        foreach ($accountIds as $id) {
            $opening = (float) ($openingsByAccount[$id] ?? 0);
            $movement = (float) ($movementsByAccount[$id] ?? 0);
            $balance = $opening + $movement;
            $balancesByAccount[$id] = $balance;
            if ($balance >= 0) {
                $sumDebit += $balance;
            } else {
                $sumCredit += abs($balance);
            }
        }

        $totalAccountsCount = Account::query()->when($request->filled('search'), function ($q) use ($request) {
            $term = $request->search;
            $q->where(function ($q) use ($term) {
                $q->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        })->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))->count();

        $totalDebit = $sumDebit;
        $totalCredit = $sumCredit;
        $difference = $totalDebit - $totalCredit;

        $journalLineSet = [];
        if ($accountIds !== []) {
            foreach (
                JournalItem::query()
                    ->whereIn('account_id', $accountIds)
                    ->distinct()
                    ->pluck('account_id')
                    ->all() as $jid
            ) {
                $journalLineSet[(int) $jid] = true;
            }
        }

        return view('finance.accounts.index', [
            'rootAccounts' => $rootAccounts,
            'totalAccountsCount' => $totalAccountsCount,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'difference' => $difference,
            'balancesByAccount' => $balancesByAccount,
            'journalLineSet' => $journalLineSet,
        ]);
    }
}
