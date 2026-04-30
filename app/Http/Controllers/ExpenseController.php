<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\CompanySetting;
use App\Models\CostCenter;
use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\UniversalImportService;
use App\Support\ErpRoles;
use App\Support\DefaultLedgerAccounts;
use App\Support\ErpFilamentNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    use PersistsMorphAttachments;

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'Amount',
            'Expense Date',
            'Category',
            'Description',
            'Expense Number',
            'Account Code',
            'Account Name',
            'Tax Amount',
            'Total Amount',
            'Status',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="expenses-import-template.csv"',
        ]);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_EXPENSES);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? $e->getMessage();

            return back()->withInput()->with('error', $message);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $created = (int) ($summary['created'] ?? 0);
        $updated = (int) ($summary['updated'] ?? 0);
        $failed = (int) ($summary['failed'] ?? 0);
        $total = $created + $updated + $failed;
        ErpFilamentNotification::successImport(
            'تم استيراد المصروفات بنجاح',
            "إجمالي الصفوف المعالجة: {$total} — إضافة: {$created} — تحديث: {$updated} — فشل: {$failed}"
        );

        return redirect()
            ->route('finance.expenses.index')
            ->with('import_result', $summary);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        if ($status === 'unposted') {
            $status = 'draft';
        }
        $supplierId = $request->query('supplier_id');
        $supplierId = ($supplierId !== null && $supplierId !== '') ? (int) $supplierId : null;
        $expenseAccountId = $request->query('expense_account_id');
        $expenseAccountId = ($expenseAccountId !== null && $expenseAccountId !== '') ? (int) $expenseAccountId : null;
        $costCenterId = $request->query('cost_center_id');
        $costCenterId = ($costCenterId !== null && $costCenterId !== '') ? (int) $costCenterId : null;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $isSysOwner = (int) auth()->id() === 1;

        if ($supplierId && ! ($isSysOwner
            ? Supplier::withoutGlobalScopes()->whereKey($supplierId)->exists()
            : Supplier::query()->whereKey($supplierId)->exists())) {
            $supplierId = null;
        }
        if ($expenseAccountId && ! ($isSysOwner
            ? Account::withoutGlobalScopes()->whereKey($expenseAccountId)->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])->exists()
            : Account::query()->whereKey($expenseAccountId)->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])->exists())) {
            $expenseAccountId = null;
        }
        if ($costCenterId && ! ($isSysOwner
            ? CostCenter::withoutGlobalScopes()->whereKey($costCenterId)->exists()
            : CostCenter::query()->whereKey($costCenterId)->exists())) {
            $costCenterId = null;
        }

        $baseQuery = $this->expensesIndexBaseQuery($search, $status, $supplierId, $expenseAccountId, $costCenterId, $dateFrom, $dateTo);

        $expenses = (clone $baseQuery)
            ->with($this->expenseIndexRelations())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $statsRow = (clone $baseQuery)
            ->selectRaw('COUNT(*) as expense_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as sum_amount')
            ->selectRaw('COALESCE(SUM(tax_amount), 0) as sum_tax')
            ->selectRaw('COALESCE(SUM(amount + COALESCE(tax_amount, 0)), 0) as sum_grand')
            ->first();

        $expenseSummary = [
            'count' => (int) ($statsRow->expense_count ?? 0),
            'sum_amount' => (float) ($statsRow->sum_amount ?? 0),
            'sum_tax' => (float) ($statsRow->sum_tax ?? 0),
            'sum_grand' => (float) ($statsRow->sum_grand ?? 0),
        ];

        $suppliers = ($isSysOwner ? Supplier::withoutGlobalScopes() : Supplier::query())
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        $filterExpenseAccounts = ($isSysOwner ? Account::withoutGlobalScopes() : Account::query())
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])
            ->where(function ($query) {
                $query->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $filterCostCenters = ($isSysOwner ? CostCenter::withoutGlobalScopes() : CostCenter::query())
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance.placeholders.expenses', compact(
            'expenses',
            'search',
            'status',
            'supplierId',
            'expenseAccountId',
            'costCenterId',
            'dateFrom',
            'dateTo',
            'expenseSummary',
            'suppliers',
            'filterExpenseAccounts',
            'filterCostCenters'
        ))->with('canBulkDeleteAllExpenses', ErpRoles::canBulkDeleteAllExpensesMatchingFilters($request->user()));
    }

    /**
     * حساب المستأجر الفعلي لسند المصروف (للمستخدم 1 يُستخدم مالك السند وليس الجلسة فقط).
     */
    private function ledgerUserIdForExpense(Payment $expense): int
    {
        return (int) auth()->id() === 1 ? (int) $expense->user_id : (int) auth()->id();
    }

    /**
     * @return array<string|\Closure>
     */
    private function expenseIndexRelations(): array
    {
        if ((int) auth()->id() !== 1) {
            return ['expenseAccount', 'expenseCategory', 'supplier'];
        }

        return [
            'expenseAccount' => fn ($q) => $q->withoutGlobalScopes(),
            'expenseCategory' => fn ($q) => $q->withoutGlobalScopes(),
            'supplier' => fn ($q) => $q->withoutGlobalScopes(),
        ];
    }

    private function loadExpensePresentationRelations(Payment $expense): void
    {
        if ((int) auth()->id() !== 1) {
            $expense->load(['expenseAccount', 'expenseCategory', 'supplier', 'attachments']);

            return;
        }

        $expense->load([
            'attachments',
            'expenseAccount' => fn ($q) => $q->withoutGlobalScopes(),
            'expenseCategory' => fn ($q) => $q->withoutGlobalScopes(),
            'supplier' => fn ($q) => $q->withoutGlobalScopes(),
        ]);
    }

    /**
     * @param  mixed  $dateFrom
     * @param  mixed  $dateTo
     */
    private function expensesIndexBaseQuery(string $search, string $status, ?int $supplierId, ?int $expenseAccountId, ?int $costCenterId, $dateFrom, $dateTo): Builder
    {
        $ownerSeesAll = (int) auth()->id() === 1;

        $base = $ownerSeesAll
            ? Payment::withoutGlobalScopes()->where('type', 'expense')
            : Payment::query()->where('type', 'expense');

        return $base
            ->when($search !== '', function ($query) use ($search, $ownerSeesAll) {
                $query->where(function ($inner) use ($search, $ownerSeesAll) {
                    $inner->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('expense_number', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%')
                        ->orWhere('id', 'like', '%'.$search.'%')
                        ->orWhereHas('expenseAccount', function ($accountQuery) use ($search, $ownerSeesAll) {
                            if ($ownerSeesAll) {
                                $accountQuery->withoutGlobalScopes();
                            }
                            $accountQuery->where('code', 'like', '%'.$search.'%')
                                ->orWhere('name_ar', 'like', '%'.$search.'%')
                                ->orWhere('name_en', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search, $ownerSeesAll) {
                            if ($ownerSeesAll) {
                                $supplierQuery->withoutGlobalScopes();
                            }
                            $supplierQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('name_ar', 'like', '%'.$search.'%')
                                ->orWhere('code', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($status === 'posted', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'posted')
                        ->orWhereNotNull('journal_entry_id');
                });
            })
            ->when($status === 'draft', function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'draft')
                        ->orWhere(function ($q2) {
                            $q2->whereNull('status')
                                ->whereNull('journal_entry_id');
                        });
                });
            })
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($expenseAccountId, fn ($query) => $query->where('expense_account_id', $expenseAccountId))
            ->when($costCenterId, fn ($query) => $query->where('cost_center_id', $costCenterId))
            ->when(filled($dateFrom), function ($query) use ($dateFrom) {
                $query->whereDate('date', '>=', Carbon::parse($dateFrom)->format('Y-m-d'));
            })
            ->when(filled($dateTo), function ($query) use ($dateTo) {
                $query->whereDate('date', '<=', Carbon::parse($dateTo)->format('Y-m-d'));
            });
    }

    public function create(): View
    {
        // حسابات مصروف قابلة للترحيل: حسابات فرعية (ورقة) أو المسموح بها صراحة للترحيل المباشر
        $expenseAccounts = Account::query()
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])
            ->where(function ($query) {
                $query->whereNotNull('parent_id')
                    ->orWhere('allow_direct_posting', true);
            })
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $categories = ExpenseCategory::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $costCenters = CostCenter::query()
            ->where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $suppliers = Supplier::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $nextExpenseNumber = Payment::generateNextExpenseNumberForUser((int) (auth()->id() ?? 1));

        $bankAccounts = BankAccount::query()
            ->where('status', 'active')
            ->whereNotNull('ledger_account_id')
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get(['id', 'bank_name', 'account_number']);

        return view('finance.expenses.create', compact('categories', 'costCenters', 'expenseAccounts', 'suppliers', 'nextExpenseNumber', 'bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $uid = (int) ($user?->id ?? auth()->id() ?? 1);
        if (! in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)) {
            $request->merge(['bank_account_id' => null]);
        }
        $data = $request->validate([
            'expense_category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $uid)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'reference' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,bank,card,check'],
            'bank_account_id' => [
                Rule::requiredIf(fn () => in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)),
                'nullable',
                'integer',
                Rule::exists('bank_accounts', 'id')->where(function ($q) use ($uid): void {
                    $q->where('user_id', $uid)->whereNotNull('ledger_account_id');
                }),
            ],
            'notes' => ['nullable', 'string', 'max:3000'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'date.required' => 'تاريخ المصروف مطلوب.',
            'account_id.required' => 'اختر الحساب المحاسبي.',
            'account_id.exists' => 'الحساب المحاسبي غير صالح.',
            'amount.required' => 'أدخل مبلغ المصروف.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'tax_amount.numeric' => 'مبلغ الضريبة يجب أن يكون رقماً.',
            'tax_amount.min' => 'مبلغ الضريبة لا يمكن أن يكون سالباً.',
            'payment_method.required' => 'اختر طريقة الدفع.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
            'bank_account_id.required' => 'اختر الحساب البنكي عند الدفع بنك أو شيك أو بطاقة.',
            'bank_account_id.exists' => 'الحساب البنكي غير صالح أو غير مربوط بدليل الحسابات.',
            'expense_category_id.exists' => 'تصنيف المصروف غير صالح.',
            'cost_center_id.exists' => 'مركز التكلفة غير صالح.',
            'supplier_id.exists' => 'المورد غير صالح.',
        ]);

        $expenseAccount = Account::query()
            ->where('id', $data['account_id'])
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])
            ->first();

        if (! $expenseAccount) {
            return back()
                ->withInput()
                ->withErrors(['account_id' => 'الحساب المختار ليس حساب مصروف/أصل صالح.']);
        }

        $amount = (float) $data['amount'];
        $taxAmount = (float) ($data['tax_amount'] ?? 0);

        $expenseNumber = Payment::generateNextExpenseNumberForUser($uid);

        $bankAccountId = in_array((string) ($data['payment_method'] ?? ''), ['bank', 'check', 'card'], true)
            ? (int) ($data['bank_account_id'] ?? 0)
            : null;

        $notes = $data['notes'] ?? null;
        if ($notes === null && ! empty($data['description'])) {
            $notes = $data['description'];
        } elseif ($notes !== null && ! empty($data['description'])) {
            $notes = $data['description']."\n\n".$notes;
        }

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        DB::transaction(function () use ($data, $expenseAccount, $amount, $taxAmount, $user, $uid, $expenseNumber, $notes, $uploads, $bankAccountId): void {
            $payment = Payment::query()->create([
                'user_id' => $uid,
                'expense_number' => $expenseNumber,
                'supplier_id' => $data['supplier_id'] ?? null,
                'expense_account_id' => $expenseAccount->id,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'notes' => $notes,
                'type' => 'expense',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'bank_account_id' => $bankAccountId,
                'journal_entry_id' => null,
                'status' => 'draft',
                'created_by' => $user?->id ?? $uid,
            ]);

            $this->persistMorphAttachments($payment, $uploads, $uid, 'expenses');
        });

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'تم إنشاء المصروف كمسودة. يُرحَّل إلى الأستاذ بعد الاعتماد.');
    }

    public function edit(Request $request, Payment $expense): View|RedirectResponse
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        $user = $request->user();
        $expenseIsPosted = $this->expenseIsPosted($expense);
        $isSuperAdmin = $this->userIsExpenseSuperAdmin($user);

        if ($expenseIsPosted && ! $isSuperAdmin) {
            return redirect()
                ->route('finance.expenses.index')
                ->with('error', 'لا يمكن تعديل مصروف معتمد إلا من قبل مسؤول النظام.');
        }

        $ledgerUid = $this->ledgerUserIdForExpense($expense);

        $expenseAccounts = Account::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])
            ->where(function ($query) use ($expense) {
                $query->where(function ($q) {
                    $q->where(function ($inner) {
                        $inner->whereNotNull('parent_id')
                            ->orWhere('allow_direct_posting', true);
                    })->where(function ($inner) {
                        $inner->where('is_active', true)
                            ->orWhereNull('is_active');
                    });
                });
                if ($expense->expense_account_id) {
                    $query->orWhere('id', $expense->expense_account_id);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $categories = ExpenseCategory::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->where(function ($query) use ($expense) {
                $query->where('status', 'active');
                if ($expense->expense_category_id) {
                    $query->orWhere('id', $expense->expense_category_id);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        $costCenters = CostCenter::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->where(function ($query) use ($expense) {
                $query->where('status', 'active');
                if ($expense->cost_center_id) {
                    $query->orWhere('id', $expense->cost_center_id);
                }
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $suppliers = Supplier::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $expense->load(['attachments']);

        $bankAccounts = BankAccount::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->where('status', 'active')
            ->whereNotNull('ledger_account_id')
            ->orderBy('bank_name')
            ->orderBy('account_number')
            ->get(['id', 'bank_name', 'account_number']);

        return view('finance.expenses.edit', compact('expense', 'categories', 'costCenters', 'expenseAccounts', 'suppliers', 'expenseIsPosted', 'isSuperAdmin', 'bankAccounts'));
    }

    public function update(Request $request, Payment $expense): RedirectResponse
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        $user = $request->user();
        $expenseIsPosted = $this->expenseIsPosted($expense);
        if ($expenseIsPosted && ! $this->userIsExpenseSuperAdmin($user)) {
            return back()
                ->withInput()
                ->withErrors(['expense' => 'لا يمكن تعديل مصروف معتمد إلا من قبل مسؤول النظام.']);
        }

        $ledgerUid = $this->ledgerUserIdForExpense($expense);
        $uid = $ledgerUid;
        if (! in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)) {
            $request->merge(['bank_account_id' => null]);
        }
        $data = $request->validate([
            'expense_category_id' => ['nullable', Rule::exists('expense_categories', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', $uid)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('user_id', $uid)],
            'reference' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:cash,bank,card,check'],
            'bank_account_id' => [
                Rule::requiredIf(fn () => in_array((string) $request->input('payment_method'), ['bank', 'check', 'card'], true)),
                'nullable',
                'integer',
                Rule::exists('bank_accounts', 'id')->where(function ($q) use ($uid): void {
                    $q->where('user_id', $uid)->whereNotNull('ledger_account_id');
                }),
            ],
            'notes' => ['nullable', 'string', 'max:3000'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'date.required' => 'تاريخ المصروف مطلوب.',
            'account_id.required' => 'اختر الحساب المحاسبي.',
            'account_id.exists' => 'الحساب المحاسبي غير صالح.',
            'amount.required' => 'أدخل مبلغ المصروف.',
            'amount.numeric' => 'المبلغ يجب أن يكون رقماً.',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر.',
            'tax_amount.numeric' => 'مبلغ الضريبة يجب أن يكون رقماً.',
            'tax_amount.min' => 'مبلغ الضريبة لا يمكن أن يكون سالباً.',
            'payment_method.required' => 'اختر طريقة الدفع.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
            'bank_account_id.required' => 'اختر الحساب البنكي عند الدفع بنك أو شيك أو بطاقة.',
            'bank_account_id.exists' => 'الحساب البنكي غير صالح أو غير مربوط بدليل الحسابات.',
            'expense_category_id.exists' => 'تصنيف المصروف غير صالح.',
            'cost_center_id.exists' => 'مركز التكلفة غير صالح.',
            'supplier_id.exists' => 'المورد غير صالح.',
        ]);

        $expenseAccount = Account::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->where('id', $data['account_id'])
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])
            ->first();

        if (! $expenseAccount) {
            return back()
                ->withInput()
                ->withErrors(['account_id' => 'الحساب المختار ليس حساب مصروف/أصل صالح.']);
        }

        $totalAmount = (float) $data['amount'] + (float) ($data['tax_amount'] ?? 0);

        $bankAccountId = in_array((string) ($data['payment_method'] ?? ''), ['bank', 'check', 'card'], true)
            ? (int) ($data['bank_account_id'] ?? 0)
            : null;

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        DB::transaction(function () use ($expense, $data, $expenseAccount, $totalAmount, $uploads, $uid, $bankAccountId): void {
            $expense->update([
                'supplier_id' => $data['supplier_id'] ?? null,
                'expense_account_id' => $expenseAccount->id,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'date' => $data['date'],
                'reference' => $data['reference'] ?? null,
                'amount' => (float) $data['amount'],
                'tax_amount' => (float) ($data['tax_amount'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'payment_method' => $data['payment_method'],
                'bank_account_id' => $bankAccountId,
            ]);

            $this->persistMorphAttachments($expense, $uploads, $uid, 'expenses');

            if ($expense->journal_entry_id) {
                $this->syncExpenseJournalEntry($expense->fresh(), $expenseAccount, $totalAmount);
            }
        });

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'تم تحديث المصروف بنجاح.');
    }

    public function approve(Request $request, Payment $expense): RedirectResponse
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        if (! $this->userCanApproveExpense($request->user())) {
            abort(403);
        }

        if ($this->expenseIsPosted($expense)) {
            return back()->with('error', 'هذا المصروف معتمد مسبقاً.');
        }

        $ledgerUid = $this->ledgerUserIdForExpense($expense);

        $expenseAccount = Account::withoutGlobalScopes()
            ->where('user_id', $ledgerUid)
            ->where('id', $expense->expense_account_id)
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])
            ->first();

        if (! $expenseAccount) {
            return back()->with('error', 'لا يوجد حساب مصروف/أصل صالح لهذا السند.');
        }

        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);
        $totalAmount = (float) $expense->amount + (float) ($expense->tax_amount ?? 0);

        DB::transaction(function () use ($expense, $expenseAccount, $totalAmount, $uid): void {
            $entry = $this->createExpenseJournalEntry($expense, $expenseAccount, $totalAmount, $uid);

            $updates = [
                'journal_entry_id' => $entry->id,
                'status' => 'posted',
            ];

            if ($expenseAccount->type === Account::TYPE_ASSET && ! $expense->fixed_asset_id) {
                $fixedAsset = $this->createFixedAssetFromCapexExpense($expense, $expenseAccount, $entry->id);
                if ($fixedAsset) {
                    $updates['fixed_asset_id'] = $fixedAsset->id;
                }
            }

            $expense->update($updates);
        });

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'تم اعتماد المصروف وترحيله إلى الأستاذ.');
    }

    public function backToDraft(Request $request, Payment $expense): RedirectResponse
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        if (! ErpRoles::canRevertApprovedExpenseToDraft($request->user())) {
            abort(403);
        }

        if (! $this->expenseIsPosted($expense)) {
            return redirect()
                ->route('finance.expenses.index')
                ->with('error', 'المصروف ليس معتمداً.');
        }

        $journalEntryIdWas = $expense->journal_entry_id ? (int) $expense->journal_entry_id : null;

        DB::transaction(function () use ($expense, $journalEntryIdWas): void {
            if ($expense->fixed_asset_id) {
                FixedAsset::query()
                    ->whereKey((int) $expense->fixed_asset_id)
                    ->where('source_payment_id', $expense->id)
                    ->delete();
            }

            if ($journalEntryIdWas) {
                JournalItem::withoutGlobalScopes()->where('journal_entry_id', $journalEntryIdWas)->delete();
                JournalEntry::withoutGlobalScopes()->whereKey($journalEntryIdWas)->delete();
            }

            $expense->update([
                'journal_entry_id' => null,
                'status' => 'draft',
                'fixed_asset_id' => null,
            ]);

            AuditLog::logFinancialControl(
                'expense_back_to_draft',
                (int) $expense->user_id,
                Payment::class,
                (int) $expense->id,
                [
                    'journal_entry_id_was' => $journalEntryIdWas,
                ]
            );
        });

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', 'تم إعادة المصروف إلى مسودة وحذف القيد المحاسبي المرتبط.');
    }

    public function print(Payment $expense): View
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        $this->loadExpensePresentationRelations($expense);
        $company = CompanySetting::query()->first();

        return view('finance.expenses.print', compact('expense', 'company'));
    }

    public function pdf(Payment $expense): Response
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        if (! $this->expenseIsPosted($expense)) {
            abort(403, 'عرض PDF متاح للمصروفات المعتمدة فقط.');
        }

        $this->loadExpensePresentationRelations($expense);
        $company = CompanySetting::query()->first();

        $logoDataUri = null;
        if ($company?->logo_url && str_starts_with((string) $company->logo_url, 'company/')) {
            if (Storage::disk('public')->exists($company->logo_url)) {
                $logoMime = Storage::disk('public')->mimeType($company->logo_url) ?: 'image/png';
                if (is_string($logoMime) && str_starts_with($logoMime, 'image/')) {
                    $logoBytes = Storage::disk('public')->get($company->logo_url);
                    if ($logoBytes !== false && $logoBytes !== '') {
                        $logoDataUri = 'data:'.$logoMime.';base64,'.base64_encode($logoBytes);
                    }
                }
            }
        }

        // تضمين الإيصال كـ data URI من القرص: يعمل على السيرفر دون جلب HTTP (يُفادي الفشل/الصفحة الفارغة عند APP_URL أو SSL خاطئ)
        $receiptDataUri = null;
        $receiptPathForPdf = $expense->attachments
            ->sortBy('id')
            ->first(function ($att): bool {
                $mime = strtolower((string) ($att->file_type ?? ''));

                return $mime !== '' && str_starts_with($mime, 'image/');
            });
        if ($receiptPathForPdf && $receiptPathForPdf->file_path && Storage::disk('public')->exists($receiptPathForPdf->file_path)) {
            $mime = Storage::disk('public')->mimeType($receiptPathForPdf->file_path) ?: 'image/jpeg';
            if (is_string($mime) && str_starts_with($mime, 'image/')) {
                $bytes = Storage::disk('public')->get($receiptPathForPdf->file_path);
                if ($bytes !== false && $bytes !== '') {
                    $receiptDataUri = 'data:'.$mime.';base64,'.base64_encode($bytes);
                }
            }
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($expense->expense_number ?? 'EXP-'.$expense->id));
        $filename = 'expense-'.$safeName.'.pdf';

        try {
            return Pdf::loadView('finance.expenses.pdf', [
                'expense' => $expense,
                'company' => $company,
                'logoDataUri' => $logoDataUri,
                'receiptDataUri' => $receiptDataUri,
            ])
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false)
                ->setOption('isHtml5ParserEnabled', true)
                ->stream($filename);
        } catch (\Throwable $e) {
            Log::error('Expense PDF generation failed', [
                'expense_id' => $expense->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            abort(500, 'تعذّر إنشاء ملف PDF. راجع سجلات الخادم.');
        }
    }

    public function destroy(Request $request, Payment $expense): RedirectResponse
    {
        if ($expense->type !== 'expense') {
            abort(404);
        }

        $posted = $this->expenseIsPosted($expense);

        if ($posted && ! ErpRoles::canHardDeleteApprovedExpense($request->user())) {
            abort(403);
        }

        if (! $posted && ! ErpRoles::canDeleteExpenseDraft($request->user())) {
            abort(403);
        }

        DB::transaction(function () use ($expense): void {
            $this->performExpenseDeletionWithAudit($expense, true);
        });

        return redirect()
            ->route('finance.expenses.index')
            ->with('success', $posted ? 'تم الحذف النهائي للمصروف المعتمد والقيد المرتبط.' : 'تم حذف مسودة المصروف.');
    }

    /**
     * حذف جميع المصروفات المطابقة لنفس فلاتر القائمة الحالية — سوبر أدمن فقط.
     */
    public function destroyAllMatchingFilters(Request $request): RedirectResponse
    {
        if (! ErpRoles::canBulkDeleteAllExpensesMatchingFilters($request->user())) {
            abort(403);
        }

        $request->validate([
            'confirm_bulk_delete' => ['accepted'],
        ], [], [
            'confirm_bulk_delete' => 'تأكيد المسح الجماعي',
        ]);

        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        if ($status === 'unposted') {
            $status = 'draft';
        }
        $supplierId = $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null;
        $expenseAccountId = $request->filled('expense_account_id') ? (int) $request->input('expense_account_id') : null;
        $costCenterId = $request->filled('cost_center_id') ? (int) $request->input('cost_center_id') : null;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $isSysOwner = (int) auth()->id() === 1;
        if ($supplierId && ! ($isSysOwner
            ? Supplier::withoutGlobalScopes()->whereKey($supplierId)->exists()
            : Supplier::query()->whereKey($supplierId)->exists())) {
            $supplierId = null;
        }
        if ($expenseAccountId && ! ($isSysOwner
            ? Account::withoutGlobalScopes()->whereKey($expenseAccountId)->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])->exists()
            : Account::query()->whereKey($expenseAccountId)->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_ASSET])->exists())) {
            $expenseAccountId = null;
        }
        if ($costCenterId && ! ($isSysOwner
            ? CostCenter::withoutGlobalScopes()->whereKey($costCenterId)->exists()
            : CostCenter::query()->whereKey($costCenterId)->exists())) {
            $costCenterId = null;
        }

        $baseQuery = $this->expensesIndexBaseQuery($search, $status, $supplierId, $expenseAccountId, $costCenterId, $dateFrom, $dateTo);
        $total = (clone $baseQuery)->count();

        if ($total === 0) {
            return redirect()
                ->route('finance.expenses.index', $request->only(['search', 'status', 'supplier_id', 'expense_account_id', 'cost_center_id', 'date_from', 'date_to']))
                ->with('error', 'لا توجد مصروفات مطابقة للفلاتر الحالية.');
        }

        $deleted = 0;
        $filterSnapshot = $request->only(['search', 'status', 'supplier_id', 'expense_account_id', 'cost_center_id', 'date_from', 'date_to']);

        DB::transaction(function () use ($baseQuery, &$deleted, $request, $total, $filterSnapshot): void {
            while (true) {
                $batch = (clone $baseQuery)->orderBy('id')->limit(80)->get();
                if ($batch->isEmpty()) {
                    break;
                }
                foreach ($batch as $expense) {
                    if ($expense->type !== 'expense') {
                        continue;
                    }
                    $this->performExpenseDeletionWithAudit($expense, false);
                    $deleted++;
                }
            }

            AuditLog::logFinancialControl(
                'expenses_bulk_delete_super',
                (int) $request->user()->id,
                null,
                null,
                [
                    'description' => 'مسح جماعي للمصروفات (سوبر أدمن): '.$deleted.' سنداً من أصل '.$total.' مطابق للفلاتر.',
                    'deleted_count' => $deleted,
                    'expected_count' => $total,
                    'filters' => $filterSnapshot,
                ]
            );
        });

        return redirect()
            ->route('finance.expenses.index', $request->only(['search', 'status', 'supplier_id', 'expense_account_id', 'cost_center_id', 'date_from', 'date_to']))
            ->with('success', 'تم حذف '.$deleted.' مصروفاً مطابقاً للفلاتر الحالية.');
    }

    /**
     * حذف السند من DB مع القيد؛ التدقيق لكل سند اختياري (يُعطّل في المسح الجماعي ثم يُسجَّل ملخص واحد).
     */
    private function performExpenseDeletionWithAudit(Payment $expense, bool $withPerRecordAudit): void
    {
        $posted = $this->expenseIsPosted($expense);
        $paymentId = (int) $expense->id;
        $tenantUid = (int) $expense->user_id;
        $journalEntryIdWas = $expense->journal_entry_id ? (int) $expense->journal_entry_id : null;
        $amount = (float) ($expense->amount ?? 0);
        $taxAmount = (float) ($expense->tax_amount ?? 0);
        $totalAmount = (float) ($expense->total_amount ?? ($amount + $taxAmount));
        $expenseNumber = $expense->expense_number;

        if ($withPerRecordAudit) {
            if ($posted) {
                AuditLog::logFinancialControl(
                    'expense_hard_delete',
                    $tenantUid,
                    Payment::class,
                    $paymentId,
                    [
                        'description' => 'قام السوبر أدمن بحذف المصروف رقم '.$paymentId.' نهائياً مع كافة قيوده المالية.',
                        'original_user_id' => $tenantUid,
                        'amount' => $amount,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'expense_number' => $expenseNumber,
                        'journal_entry_id_was' => $journalEntryIdWas,
                    ]
                );
            } else {
                AuditLog::logFinancialControl(
                    'expense_delete_draft',
                    $tenantUid,
                    Payment::class,
                    $paymentId,
                    [
                        'description' => 'حذف مسودة مصروف رقم '.$paymentId,
                        'original_user_id' => $tenantUid,
                        'amount' => $amount,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'expense_number' => $expenseNumber,
                        'posted' => false,
                    ]
                );
            }
        }

        if ($journalEntryIdWas) {
            JournalItem::withoutGlobalScopes()->where('journal_entry_id', $journalEntryIdWas)->delete();
            JournalEntry::withoutGlobalScopes()->whereKey($journalEntryIdWas)->delete();
        }

        $expense->delete();
    }

    private function expenseIsPosted(Payment $expense): bool
    {
        if ($expense->type !== 'expense') {
            return false;
        }

        if (($expense->status ?? '') === 'posted') {
            return true;
        }

        if (($expense->status ?? '') === 'cancelled') {
            return false;
        }

        return $expense->journal_entry_id !== null;
    }

    private function userCanApproveExpense(?Authenticatable $user): bool
    {
        return $user instanceof User
            && in_array($user->role, ['admin', 'supervisor'], true);
    }

    private function userIsExpenseSuperAdmin(?Authenticatable $user): bool
    {
        return $user instanceof User && $user->role === 'admin';
    }

    private function createFixedAssetFromCapexExpense(Payment $expense, Account $assetAccount, int $journalEntryId): ?FixedAsset
    {
        $cost = (float) (($expense->amount ?? 0) + ($expense->tax_amount ?? 0));
        if ($cost <= 0) {
            return null;
        }

        $code = $this->nextFixedAssetCode();
        $name = $expense->reference ?: ('أصل رأسمالي من مصروف '.$expense->expense_number);
        $uid = (int) $expense->user_id;
        $fac = FixedAssetCategory::ensureDefaultForUser($uid);

        return FixedAsset::query()->create([
            'asset_code' => $code,
            'name' => $name,
            'name_ar' => $name,
            'fixed_asset_category_id' => $fac->id,
            'cost_center_id' => $expense->cost_center_id,
            'ledger_account_id' => (int) $fac->ledger_asset_account_id,
            'payment_method' => $expense->payment_method ?? 'cash',
            'bank_account_id' => $expense->bank_account_id,
            'journal_entry_id' => $journalEntryId,
            'source_payment_id' => $expense->id,
            'category' => $fac->name_ar,
            'description' => $expense->notes,
            'acquisition_date' => $expense->date,
            'acquisition_cost' => $cost,
            'book_value' => $cost,
            'status' => 'in_use',
        ]);
    }

    private function nextFixedAssetCode(): string
    {
        $max = 0;
        $codes = FixedAsset::query()->where('asset_code', 'like', 'FA-%')->pluck('asset_code');
        foreach ($codes as $code) {
            if (preg_match('/^FA-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'FA-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * حساب الدائن في قيد المصروف: نقد → صندوق؛ بنك/شيك/بطاقة مع اختيار حساب بنكي → حساب الدليل المرتبط؛ وإلا السلوك الافتراضي القديم.
     */
    private function resolveExpenseCreditAccount(Payment $expense): Account
    {
        $ledgerUid = $this->ledgerUserIdForExpense($expense);
        $method = (string) ($expense->payment_method ?? 'cash');

        if ($method === 'cash') {
            return DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', $ledgerUid);
        }

        if (in_array($method, ['bank', 'check', 'card'], true) && $expense->bank_account_id) {
            $ba = BankAccount::withoutGlobalScopes()
                ->where('user_id', $ledgerUid)
                ->whereKey($expense->bank_account_id)
                ->first();
            if ($ba && $ba->ledger_account_id) {
                $acc = Account::withoutGlobalScopes()
                    ->where('user_id', $ledgerUid)
                    ->whereKey($ba->ledger_account_id)
                    ->first();
                if ($acc) {
                    return $acc;
                }
            }
        }

        return DefaultLedgerAccounts::paymentSourceAssetForTenant('bank', $ledgerUid);
    }

    private function createExpenseJournalEntry(Payment $expense, Account $expenseAccount, float $totalAmount, int $userId): JournalEntry
    {
        $creditAccount = $this->resolveExpenseCreditAccount($expense);
        $desc = ($expense->notes !== null && $expense->notes !== '')
            ? mb_substr((string) $expense->notes, 0, 500)
            : ('مصروف #'.($expense->reference ?? 'بدون مرجع'));

        $entry = JournalEntry::withoutGlobalScopes()->create([
            'user_id' => $userId,
            'date' => $expense->date,
            'reference' => 'EXP',
            'description' => $desc,
            'total' => $totalAmount,
        ]);

        JournalItem::withoutGlobalScopes()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expenseAccount->id,
            'description' => 'تحميل مصروف',
            'debit' => $totalAmount,
            'credit' => 0,
        ]);

        JournalItem::withoutGlobalScopes()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'description' => 'دفع مصروف',
            'debit' => 0,
            'credit' => $totalAmount,
        ]);

        return $entry;
    }

    private function syncExpenseJournalEntry(Payment $expense, Account $expenseAccount, float $totalAmount): void
    {
        $entryId = $expense->journal_entry_id;
        if (! $entryId) {
            return;
        }

        $entry = JournalEntry::withoutGlobalScopes()->find($entryId);
        if (! $entry) {
            return;
        }

        $creditAccount = $this->resolveExpenseCreditAccount($expense);
        $desc = ($expense->notes !== null && $expense->notes !== '')
            ? mb_substr((string) $expense->notes, 0, 500)
            : ('مصروف #'.($expense->reference ?? 'بدون مرجع'));

        JournalItem::withoutGlobalScopes()->where('journal_entry_id', $entry->id)->delete();

        $entry->update([
            'date' => $expense->date,
            'description' => $desc,
            'total' => $totalAmount,
        ]);

        JournalItem::withoutGlobalScopes()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expenseAccount->id,
            'description' => 'تحميل مصروف',
            'debit' => $totalAmount,
            'credit' => 0,
        ]);

        JournalItem::withoutGlobalScopes()->create([
            'journal_entry_id' => $entry->id,
            'account_id' => $creditAccount->id,
            'description' => 'دفع مصروف',
            'debit' => 0,
            'credit' => $totalAmount,
        ]);
    }
}
