<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Payroll;
use App\Models\PaySlip;
use App\Services\PayrollAccountingService;
use App\Services\PayrollGenerationService;
use App\Support\AccountingLedgerOptions;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PayrollWebController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage_payroll');

        $filterYear = (int) $request->query('filter_year', (string) now()->year);
        $filterYear = max(2000, min(2100, $filterYear));
        $filterMonthRaw = $request->query('filter_month', 'all');
        $filterMonth = is_string($filterMonthRaw) || is_numeric($filterMonthRaw)
            ? (string) $filterMonthRaw
            : 'all';

        $query = Payroll::query()
            ->where('year', $filterYear)
            ->orderByDesc('month');

        if ($filterMonth !== '' && $filterMonth !== 'all') {
            $query->where('month', (int) $filterMonth);
        }

        $payrolls = $query->paginate(20)->withQueryString();

        return view('hr.payrolls.index', compact('payrolls', 'filterYear', 'filterMonth'));
    }

    public function payslips(): View
    {
        Gate::authorize('manage_payroll');

        $slips = PaySlip::query()
            ->whereHas('payrollCycle', function ($q): void {
                $q->where('user_id', (int) auth()->id());
            })
            ->with(['employee', 'payrollCycle'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('hr.payrolls.payslips-index', compact('slips'));
    }

    public function create(): View
    {
        Gate::authorize('manage_payroll');

        $uid = (int) auth()->id();

        $departmentOptions = array_merge(
            [['value' => '', 'label' => 'جميع الأقسام']],
            Department::query()->where('user_id', $uid)->orderBy('name')->get()->map(fn (Department $d) => [
                'value' => (string) $d->id,
                'label' => $d->name,
            ])->values()->all()
        );

        $setting = CompanySetting::forTenant($uid);
        $payrollExpenseOk = (int) ($setting?->payroll_wage_expense_account_id ?? 0) > 0;
        $payrollPayableOk = (int) ($setting?->payroll_wages_payable_account_id ?? 0) > 0;
        $accountingLinksReady = $payrollExpenseOk && $payrollPayableOk;

        $arMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
            7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
        $quickMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $d = now()->subMonths($i)->startOfMonth();
            $m = (int) $d->month;
            $y = (int) $d->year;
            $quickMonths[] = [
                'y' => $y,
                'm' => $m,
                'label' => ($arMonths[$m] ?? (string) $m).' '.$y,
                'start' => $d->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $d->copy()->endOfMonth()->format('Y-m-d'),
                'nameSuggest' => 'مسير رواتب — '.($arMonths[$m] ?? '').' '.$y,
            ];
        }

        return view('hr.payrolls.create', compact(
            'departmentOptions',
            'accountingLinksReady',
            'payrollExpenseOk',
            'payrollPayableOk',
            'quickMonths',
            'arMonths'
        ));
    }

    public function store(Request $request, PayrollGenerationService $generator): RedirectResponse
    {
        Gate::authorize('manage_payroll');

        $uid = (int) $request->user()->id;

        if ($request->input('department_id') === '' || $request->input('department_id') === null) {
            $request->merge(['department_id' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('user_id', $uid)],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $start = Carbon::parse($data['period_start'])->startOfDay();
        $end = Carbon::parse($data['period_end'])->startOfDay();
        if ($start->format('Y-m') !== $end->format('Y-m')) {
            return back()
                ->withInput()
                ->with('error', 'بداية الفترة ونهايتها يجب أن تكونا داخل نفس الشهر والسنة المحاسبية للدورة.');
        }

        $year = (int) $start->year;
        $month = (int) $start->month;

        $exists = Payroll::query()
            ->where('user_id', $uid)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with('error', 'يوجد بالفعل دورة رواتب لنفس الشهر والسنة.');
        }

        $paymentDate = isset($data['payment_date']) && $data['payment_date'] !== null && $data['payment_date'] !== ''
            ? Carbon::parse($data['payment_date'])->toDateString()
            : null;

        $deptId = isset($data['department_id']) && (int) $data['department_id'] > 0
            ? (int) $data['department_id']
            : null;

        $payroll = $generator->generate(
            $uid,
            $year,
            $month,
            $data['notes'] ?? null,
            $paymentDate,
            [
                'name' => $data['name'],
                'department_id' => $deptId,
                'period_start' => $start->toDateString(),
                'period_end' => Carbon::parse($data['period_end'])->toDateString(),
            ]
        );

        return redirect()
            ->route('hr.payrolls.show', $payroll)
            ->with('success', 'تم إنشاء دورة الرواتب كمسودة. راجع القسائم والبنود ثم اعتمد.');
    }

    public function show(Payroll $payroll): View
    {
        Gate::authorize('manage_payroll');

        $payroll->load(['paySlips.employee', 'paySlips.items', 'accrualJournalEntry', 'paymentJournalEntry', 'department']);

        $uid = (int) auth()->id();
        $paymentAccountOptions = AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid);
        $defaultPaymentAccountId = CompanySetting::forTenant($uid)?->payroll_default_payment_account_id;

        $setting = CompanySetting::forTenant($uid);
        $payrollExpenseOk = (int) ($setting?->payroll_wage_expense_account_id ?? 0) > 0;
        $payrollPayableOk = (int) ($setting?->payroll_wages_payable_account_id ?? 0) > 0;
        $accountingLinksReady = $payrollExpenseOk && $payrollPayableOk;

        return view('hr.payrolls.show', compact(
            'payroll',
            'paymentAccountOptions',
            'defaultPaymentAccountId',
            'accountingLinksReady',
            'payrollExpenseOk',
            'payrollPayableOk'
        ));
    }

    public function approve(Payroll $payroll, PayrollAccountingService $accounting): RedirectResponse
    {
        Gate::authorize('manage_payroll');

        if ($payroll->status !== Payroll::STATUS_DRAFT) {
            return redirect()
                ->route('hr.payrolls.show', $payroll)
                ->with('error', 'يمكن اعتماد دورة الرواتب في حالة «مسودة» فقط.');
        }

        $uid = (int) auth()->id();
        $createdBy = (int) auth()->id();

        try {
            DB::transaction(function () use ($payroll, $accounting, $uid, $createdBy) {
                $accrualId = null;
                if ((float) $payroll->total_amount > 0) {
                    $entry = $accounting->createAccrualEntry($payroll, $uid, $createdBy);
                    $accrualId = $entry?->id;
                }
                $payroll->update([
                    'status' => Payroll::STATUS_APPROVED,
                    'accrual_journal_entry_id' => $accrualId,
                ]);
            });
        } catch (RuntimeException $e) {
            return redirect()
                ->route('hr.payrolls.show', $payroll)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('hr.payrolls.show', $payroll)
            ->with('success', 'تم اعتماد دورة الرواتب'.((float) $payroll->total_amount > 0 ? ' وإثبات الاستحقاق محاسبياً.' : '.'));
    }

    public function pay(Request $request, Payroll $payroll, PayrollAccountingService $accounting): RedirectResponse
    {
        Gate::authorize('manage_payroll');

        if ($payroll->status !== Payroll::STATUS_APPROVED) {
            return redirect()
                ->route('hr.payrolls.show', $payroll)
                ->with('error', 'يمكن دفع الرواتب في حالة «معتمد» فقط.');
        }

        if ($payroll->payment_journal_entry_id) {
            return redirect()
                ->route('hr.payrolls.show', $payroll)
                ->with('error', 'تم تسجيل دفع هذه الدورة مسبقاً.');
        }

        $uid = (int) $request->user()->id;
        $cashIds = collect(AccountingLedgerOptions::cashEquivalentAssetAccountsForUser($uid))
            ->pluck('value')
            ->map(fn ($v) => (int) $v)
            ->all();

        $hasAmount = (float) $payroll->total_amount > 0.0001;
        $rules = [
            'payment_date' => ['required', 'date'],
        ];
        if ($hasAmount) {
            $rules['payment_account_id'] = ['required', 'integer', Rule::in($cashIds)];
        } else {
            $rules['payment_account_id'] = ['nullable', 'integer', Rule::in($cashIds)];
        }

        $data = $request->validate($rules);
        $accountId = isset($data['payment_account_id']) && $data['payment_account_id'] !== null && $data['payment_account_id'] !== ''
            ? (int) $data['payment_account_id']
            : null;

        try {
            $accounting->postPaymentAndMarkPaid(
                $payroll,
                $uid,
                (int) $request->user()->id,
                $accountId,
                Carbon::parse($data['payment_date'])->toDateString()
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->route('hr.payrolls.show', $payroll)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('hr.payrolls.show', $payroll)
            ->with('success', 'تم تسجيل دفع الرواتب وإنشاء قيد الصرف عند الاقتضاء.');
    }
}
