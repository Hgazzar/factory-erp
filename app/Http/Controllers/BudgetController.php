<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Budget;
use App\Models\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $currentYear = (int) now()->year;
        $fiscalYear = $request->query('fiscal_year');
        $fiscalYear = ($fiscalYear === null || $fiscalYear === '') ? null : (int) $fiscalYear;
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $showArchived = (bool) $request->boolean('show_archived');

        $baseQuery = Budget::query()
            ->with(['items', 'items.account', 'items.costCenter'])
            ->when(! $showArchived, fn ($query) => $query->whereNull('archived_at'))
            ->when($fiscalYear !== null, fn ($query) => $query->where('fiscal_year', $fiscalYear))
            ->when(in_array($status, ['draft', 'active', 'closed'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhereRaw('CAST(fiscal_year AS TEXT) LIKE ?', ['%' . $search . '%'])
                        ->orWhereHas('items.costCenter', function ($costCenterQuery) use ($search) {
                            $costCenterQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('code', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest();

        $allFiltered = (clone $baseQuery)->get();
        $stats = $this->buildStats($allFiltered);

        $budgets = $baseQuery->paginate(15)->withQueryString();
        $budgets->getCollection()->transform(fn (Budget $budget) => $this->attachComputedAmounts($budget));

        $fiscalYears = Budget::query()
            ->select('fiscal_year')
            ->distinct()
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year')
            ->all();
        if (! in_array($currentYear, $fiscalYears, true)) {
            array_unshift($fiscalYears, $currentYear);
        }

        return view('finance.budgets.index', compact('budgets', 'stats', 'fiscalYear', 'status', 'search', 'fiscalYears', 'showArchived'));
    }

    public function create(): View
    {
        $currentYear = (int) now()->year;
        $fiscalYears = collect(range($currentYear - 1, $currentYear + 5))->values();

        return view('finance.budgets.create', [
            'accountsTree' => $this->getAccountsTree(),
            'costCenters' => CostCenter::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'fiscalYears' => $fiscalYears,
            'defaultYear' => $currentYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_REVENUE])),
            ],
            'items.*.cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'items.*.jan' => ['nullable', 'numeric', 'min:0'],
            'items.*.feb' => ['nullable', 'numeric', 'min:0'],
            'items.*.mar' => ['nullable', 'numeric', 'min:0'],
            'items.*.apr' => ['nullable', 'numeric', 'min:0'],
            'items.*.may' => ['nullable', 'numeric', 'min:0'],
            'items.*.jun' => ['nullable', 'numeric', 'min:0'],
            'items.*.jul' => ['nullable', 'numeric', 'min:0'],
            'items.*.aug' => ['nullable', 'numeric', 'min:0'],
            'items.*.sep' => ['nullable', 'numeric', 'min:0'],
            'items.*.oct' => ['nullable', 'numeric', 'min:0'],
            'items.*.nov' => ['nullable', 'numeric', 'min:0'],
            'items.*.dec' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data): void {
            $budget = Budget::query()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'fiscal_year' => (int) $data['fiscal_year'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'],
            ]);

            $monthKeys = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            $items = collect($data['items'])
                ->map(fn (array $item) => [
                    'account_id' => (int) $item['account_id'],
                    'cost_center_id' => !empty($item['cost_center_id']) ? (int) $item['cost_center_id'] : null,
                    'monthly_amounts' => collect($monthKeys)->mapWithKeys(fn (string $key) => [$key => (float) ($item[$key] ?? 0)])->all(),
                    'planned_amount' => (float) collect($monthKeys)->sum(fn (string $key) => (float) ($item[$key] ?? 0)),
                ])
                ->groupBy(fn (array $item) => $item['account_id'] . '|' . ($item['cost_center_id'] ?? 0))
                ->map(function ($rows) use ($monthKeys) {
                    $base = $rows->first();
                    $monthly = [];
                    foreach ($monthKeys as $key) {
                        $monthly[$key] = (float) $rows->sum(fn (array $row) => (float) ($row['monthly_amounts'][$key] ?? 0));
                    }
                    return [
                    'account_id' => (int) $base['account_id'],
                    'cost_center_id' => $base['cost_center_id'],
                    'monthly_amounts' => $monthly,
                    'planned_amount' => (float) collect($rows)->sum('planned_amount'),
                    ];
                })
                ->values()
                ->all();

            $budget->items()->createMany($items);
        });

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'تم إنشاء الميزانية بنجاح.');
    }

    public function show(Budget $budget): View
    {
        $budget->load(['items.account', 'items.costCenter']);
        $analysis = $this->buildBudgetAnalysis($budget, true);

        return view('finance.budgets.show', compact('budget', 'analysis'));
    }

    public function edit(Budget $budget): View
    {
        if ($budget->archived_at || in_array($budget->status, ['closed', 'active'], true)) {
            return redirect()
                ->route('finance.budgets.show', $budget)
                ->with('error', 'لا يمكن تعديل موازنة مؤرشفة أو مغلقة أو نشطة.');
        }

        $budget->load(['items.account']);
        $currentYear = (int) now()->year;
        $fiscalYears = collect(range($currentYear - 1, $currentYear + 5))->values();

        return view('finance.budgets.edit', [
            'budget' => $budget,
            'accountsTree' => $this->getAccountsTree(),
            'costCenters' => CostCenter::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'fiscalYears' => $fiscalYears,
        ]);
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        if ($budget->archived_at || in_array($budget->status, ['closed', 'active'], true)) {
            return redirect()
                ->route('finance.budgets.show', $budget)
                ->with('error', 'لا يمكن تعديل موازنة مؤرشفة أو مغلقة أو نشطة.');
        }

        $uid = (int) ($request->user()?->id ?? auth()->id() ?? 1);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_REVENUE])),
            ],
            'items.*.cost_center_id' => ['nullable', Rule::exists('cost_centers', 'id')->where('user_id', $uid)],
            'items.*.jan' => ['nullable', 'numeric', 'min:0'],
            'items.*.feb' => ['nullable', 'numeric', 'min:0'],
            'items.*.mar' => ['nullable', 'numeric', 'min:0'],
            'items.*.apr' => ['nullable', 'numeric', 'min:0'],
            'items.*.may' => ['nullable', 'numeric', 'min:0'],
            'items.*.jun' => ['nullable', 'numeric', 'min:0'],
            'items.*.jul' => ['nullable', 'numeric', 'min:0'],
            'items.*.aug' => ['nullable', 'numeric', 'min:0'],
            'items.*.sep' => ['nullable', 'numeric', 'min:0'],
            'items.*.oct' => ['nullable', 'numeric', 'min:0'],
            'items.*.nov' => ['nullable', 'numeric', 'min:0'],
            'items.*.dec' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($budget, $data): void {
            $monthKeys = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
            $budget->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'fiscal_year' => (int) $data['fiscal_year'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $items = collect($data['items'])
                ->map(fn (array $item) => [
                    'account_id' => (int) $item['account_id'],
                    'cost_center_id' => !empty($item['cost_center_id']) ? (int) $item['cost_center_id'] : null,
                    'monthly_amounts' => collect($monthKeys)->mapWithKeys(fn (string $key) => [$key => (float) ($item[$key] ?? 0)])->all(),
                    'planned_amount' => (float) collect($monthKeys)->sum(fn (string $key) => (float) ($item[$key] ?? 0)),
                ])
                ->groupBy(fn (array $item) => $item['account_id'] . '|' . ($item['cost_center_id'] ?? 0))
                ->map(function ($rows) use ($monthKeys) {
                    $base = $rows->first();
                    $monthly = [];
                    foreach ($monthKeys as $key) {
                        $monthly[$key] = (float) $rows->sum(fn (array $row) => (float) ($row['monthly_amounts'][$key] ?? 0));
                    }
                    return [
                        'account_id' => (int) $base['account_id'],
                        'cost_center_id' => $base['cost_center_id'],
                        'monthly_amounts' => $monthly,
                        'planned_amount' => (float) collect($rows)->sum('planned_amount'),
                    ];
                })
                ->values()
                ->all();

            $budget->items()->delete();
            $budget->items()->createMany($items);
        });

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'تم تحديث الميزانية بنجاح.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        if ($budget->status !== 'draft') {
            return redirect()
                ->route('finance.budgets.index')
                ->with('error', 'يمكن حذف الموازنة في حالة المسودة فقط.');
        }

        DB::transaction(function () use ($budget): void {
            $budget->items()->delete();
            $budget->delete();
        });

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'تم حذف الموازنة بنجاح.');
    }

    public function activate(Budget $budget): RedirectResponse
    {
        if ($budget->status !== 'draft') {
            return redirect()
                ->route('finance.budgets.index')
                ->with('error', 'يمكن تفعيل الموازنة من حالة المسودة فقط.');
        }

        $budget->status = 'active';
        $budget->save();

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'تم تفعيل الموازنة بنجاح.');
    }

    public function close(Budget $budget): RedirectResponse
    {
        if ($budget->status !== 'active') {
            return redirect()
                ->route('finance.budgets.index')
                ->with('error', 'يمكن إغلاق الموازنة النشطة فقط.');
        }

        DB::transaction(function () use ($budget): void {
            $budget->load(['items.account']);
            $analysis = $this->buildBudgetAnalysis($budget, false);
            $budget->status = 'closed';
            $budget->closed_at = now();
            $budget->final_snapshot = $analysis;
            $budget->save();
        });

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'تم إغلاق الموازنة وحفظ الصورة النهائية للفروقات.');
    }

    public function archive(Budget $budget): RedirectResponse
    {
        if ($budget->status !== 'closed') {
            return redirect()
                ->route('finance.budgets.index')
                ->with('error', 'الأرشفة متاحة للموازنات المغلقة فقط.');
        }

        $budget->archived_at = now();
        $budget->save();

        return redirect()
            ->route('finance.budgets.index')
            ->with('success', 'تمت أرشفة الموازنة بنجاح.');
    }

    public function export(Budget $budget): View
    {
        $budget->load(['items.account', 'items.costCenter']);
        $analysis = $this->buildBudgetAnalysis($budget, true);

        return view('finance.budgets.export', compact('budget', 'analysis'));
    }

    private function buildStats($budgets): array
    {
        $planned = 0.0;
        $actual = 0.0;
        $activeCount = 0;

        foreach ($budgets as $budget) {
            $computed = $this->attachComputedAmounts($budget);
            $planned += (float) $computed->planned_total;
            $actual += (float) $computed->actual_total;
            if ($budget->status === 'active') {
                $activeCount++;
            }
        }

        $variance = $actual - $planned;
        $variancePercent = $planned > 0 ? ($variance / $planned) * 100 : 0;

        return [
            'planned' => $planned,
            'actual' => $actual,
            'variance' => $variance,
            'variance_percent' => $variancePercent,
            'active_count' => $activeCount,
        ];
    }

    private function attachComputedAmounts(Budget $budget): Budget
    {
        $analysis = $this->buildBudgetAnalysis($budget, true);
        $budget->planned_total = (float) ($analysis['totals']['planned'] ?? 0);
        $budget->actual_total = (float) ($analysis['totals']['actual'] ?? 0);
        $budget->variance = (float) ($analysis['totals']['variance'] ?? 0);

        return $budget;
    }

    private function buildBudgetAnalysis(Budget $budget, bool $allowSnapshot): array
    {
        if ($allowSnapshot && $budget->status === 'closed' && is_array($budget->final_snapshot)) {
            return $budget->final_snapshot;
        }

        $monthLabels = [
            'jan' => 'يناير',
            'feb' => 'فبراير',
            'mar' => 'مارس',
            'apr' => 'أبريل',
            'may' => 'مايو',
            'jun' => 'يونيو',
            'jul' => 'يوليو',
            'aug' => 'أغسطس',
            'sep' => 'سبتمبر',
            'oct' => 'أكتوبر',
            'nov' => 'نوفمبر',
            'dec' => 'ديسمبر',
        ];
        $monthToNumber = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];

        $lines = [];
        $plannedTotal = 0.0;
        $actualTotal = 0.0;

        foreach ($budget->items as $item) {
            $plannedMonths = collect($monthLabels)->mapWithKeys(fn (string $label, string $key) => [
                $key => (float) (($item->monthly_amounts[$key] ?? 0)),
            ])->all();

            $actualByMonth = DB::table('journal_items')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                ->where('journal_items.account_id', $item->account_id)
                ->whereBetween('journal_entries.date', [
                    $budget->start_date->format('Y-m-d'),
                    $budget->end_date->format('Y-m-d'),
                ])
                ->selectRaw('CAST(EXTRACT(MONTH FROM journal_entries.date) AS INTEGER) as month_no, COALESCE(SUM(journal_items.debit - journal_items.credit), 0) as actual')
                ->groupBy('month_no')
                ->pluck('actual', 'month_no')
                ->all();

            $monthlyBreakdown = [];
            $planned = 0.0;
            $actual = 0.0;
            foreach ($monthLabels as $key => $label) {
                $plannedMonth = (float) ($plannedMonths[$key] ?? 0);
                $actualMonth = (float) ($actualByMonth[$monthToNumber[$key]] ?? 0);
                $planned += $plannedMonth;
                $actual += $actualMonth;
                $monthlyBreakdown[] = [
                    'key' => $key,
                    'label' => $label,
                    'planned' => $plannedMonth,
                    'actual' => $actualMonth,
                    'variance' => $actualMonth - $plannedMonth,
                ];
            }

            $variance = $actual - $planned;
            $variancePercent = $planned > 0 ? ($variance / $planned) * 100 : 0;
            $consumptionPercent = $planned > 0 ? ($actual / $planned) * 100 : 0;

            $plannedTotal += $planned;
            $actualTotal += $actual;

            $lines[] = [
                'account_id' => $item->account_id,
                'account_code' => $item->account?->code ?? '',
                'account_name' => $item->account?->name_ar ?: ($item->account?->name_en ?? '—'),
                'cost_center' => $item->costCenter ? ($item->costCenter->code . ' - ' . $item->costCenter->name) : '—',
                'planned' => $planned,
                'actual' => $actual,
                'variance' => $variance,
                'variance_percent' => $variancePercent,
                'consumption_percent' => $consumptionPercent,
                'monthly' => $monthlyBreakdown,
            ];
        }

        $varianceTotal = $actualTotal - $plannedTotal;
        $variancePercentTotal = $plannedTotal > 0 ? ($varianceTotal / $plannedTotal) * 100 : 0;

        return [
            'generated_at' => now()->toDateTimeString(),
            'totals' => [
                'planned' => $plannedTotal,
                'actual' => $actualTotal,
                'variance' => $varianceTotal,
                'variance_percent' => $variancePercentTotal,
            ],
            'lines' => $lines,
        ];
    }

    private function getAccountsTree()
    {
        $accounts = Account::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->whereIn('type', [Account::TYPE_EXPENSE, Account::TYPE_REVENUE])
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'name_en', 'parent_id']);

        $accountMap = $accounts->keyBy('id');
        return $accounts->map(function (Account $account) use ($accountMap) {
            $level = 0;
            $visited = [];
            $parentId = $account->parent_id;

            while ($parentId && isset($accountMap[$parentId]) && !in_array($parentId, $visited, true)) {
                $visited[] = $parentId;
                $level++;
                $parentId = $accountMap[$parentId]->parent_id;
            }

            $indent = str_repeat('— ', min($level, 6));
            return [
                'id' => $account->id,
                'label' => trim($indent . ($account->code . ' - ' . ($account->name_ar ?: $account->name_en))),
            ];
        })->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }
}

