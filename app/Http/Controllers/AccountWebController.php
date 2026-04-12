<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalItem;
use App\Services\UniversalImportService;
use App\Support\ErpFilamentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
            'type' => ['required', 'in:asset,liability,expense,revenue'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_bank' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $uid = (int) (auth()->id() ?? 1);
        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        $data['user_id'] = $uid;
        $data['code'] = Account::generateNextNumericCodeForUser($uid, $parentId);
        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
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
        if ($account->children()->exists()) {
            return redirect()
                ->route('finance.accounts.index', $request->only(['search', 'type']))
                ->with('error', 'لا يمكن حذف هذا الحساب لوجود حسابات فرعية أو حركات مالية مرتبطة به.');
        }

        if (JournalItem::query()->where('account_id', $account->id)->exists()) {
            return redirect()
                ->route('finance.accounts.index', $request->only(['search', 'type']))
                ->with('error', 'لا يمكن حذف هذا الحساب لوجود حسابات فرعية أو حركات مالية مرتبطة به.');
        }

        $account->delete();

        return redirect()
            ->route('finance.accounts.index', $request->only(['search', 'type']))
            ->with('success', 'تم حذف الحساب بنجاح.');
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
            $typeLabels = ['asset' => 'أصول', 'liability' => 'خصوم', 'expense' => 'مصروف', 'revenue' => 'إيراد'];
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

        $totalAccountsCount = Account::query()->when($request->filled('search'), function ($q) use ($request) {
            $term = $request->search;
            $q->where(function ($q) use ($term) {
                $q->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        })->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))->count();

        $baseForTotals = Account::query()->when($request->filled('search'), function ($q) use ($request) {
            $term = $request->search;
            $q->where(function ($q) use ($term) {
                $q->where('name_ar', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        })->when($request->filled('type'), fn ($q) => $q->where('type', $request->type));

        $totalDebit = (clone $baseForTotals)->where('opening_balance', '>', 0)->sum('opening_balance');
        $totalCredit = (clone $baseForTotals)->where('opening_balance', '<', 0)->sum('opening_balance');
        $difference = $totalDebit + $totalCredit; // opening_balance: positive = debit, negative = credit

        return view('finance.accounts.index', [
            'rootAccounts' => $rootAccounts,
            'totalAccountsCount' => $totalAccountsCount,
            'totalDebit' => $totalDebit,
            'totalCredit' => abs($totalCredit),
            'difference' => $difference,
        ]);
    }
}
