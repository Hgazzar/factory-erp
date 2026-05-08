<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Models\CrmActivity;
use App\Models\CrmCustomerMembership;
use App\Models\CrmLoyaltyProgram;
use App\Models\CrmLoyaltyAccount;
use App\Models\Customer;
use App\Services\UniversalImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerWebController extends Controller
{
    use PersistsMorphAttachments;

    public function index(Request $request): View|Response
    {
        $query = $this->baseSalesCustomersQuery($request)->orderBy('customers.code');

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "الرمز,الاسم,الهاتف,الرصيد المفتوح,الحد الائتماني,الحالة التشغيلية,المنطقة\n";
            foreach ($rows as $c) {
                $rawStatus = $c->status ?? ($c->is_active ? 'active' : 'inactive');
                $statusLabel = $rawStatus === 'active' ? 'نشط' : 'موقف';
                $bal = isset($c->open_balance) ? (string) round((float) $c->open_balance, 2) : '0';
                $credit = $c->credit_limit !== null ? (string) $c->credit_limit : '';
                $phone = trim(implode(' / ', array_filter([(string) $c->phone, (string) $c->mobile], fn ($p) => $p !== '')));
                $region = (string) ($c->region ?? '');
                $csv .= '"'.str_replace('"', '""', $c->code ?? '').'","'.str_replace('"', '""', $c->display_name ?? '').'","'.str_replace('"', '""', $phone).'","'.$bal.'","'.$credit.'","'.$statusLabel.'","'.str_replace('"', '""', $region).'"'."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="customers-sales-'.date('Y-m-d').'.csv"',
            ]);
        }

        $customers = $query->paginate(20)->withQueryString();

        $regionOptions = Customer::query()
            ->where('user_id', (int) auth()->id())
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('sales.customers.index', compact('customers', 'regionOptions'));
    }

    /**
     * مجموع ذمم مفتوحة من فواتير البيع غير المسددة بالكامل.
     */
    private function customerOpenBalanceSql(): string
    {
        return '(select coalesce(sum(greatest(coalesce(si.total, 0) - coalesce(si.paid_amount, 0), 0)), 0) from sales_invoices si where si.customer_id = customers.id)';
    }

    private function baseSalesCustomersQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $bal = $this->customerOpenBalanceSql();
        $query = Customer::query()->select('customers.*')->selectRaw("{$bal} as open_balance");

        $searchTerm = trim((string) $request->input('q', ''));
        if ($searchTerm !== '') {
            $query->where(function ($qry) use ($searchTerm) {
                $qry->where('customers.name', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.name_ar', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.code', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.email', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.vat_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customers.tax_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('op_status') && in_array($request->input('op_status'), ['active', 'inactive'], true)) {
            $query->where('customers.status', $request->input('op_status'));
        }

        if ($request->filled('region')) {
            $region = trim((string) $request->input('region'));
            if ($region !== '') {
                $query->where('customers.region', $region);
            }
        }

        if ($request->filled('balance_min') && is_numeric($request->input('balance_min'))) {
            $query->whereRaw("{$bal} >= ?", [(float) $request->input('balance_min')]);
        }

        if ($request->filled('balance_max') && is_numeric($request->input('balance_max'))) {
            $query->whereRaw("{$bal} <= ?", [(float) $request->input('balance_max')]);
        }

        return $query;
    }

    public function show(Customer $customer): View
    {
        $tenantId = (int) auth()->id();

        $customer->load([
            'attachments',
            'crmActivities.user',
            'assignedUser',
            'crmSegments',
            'crmLoyaltyAccounts.loyaltyProgram',
            'crmCustomerMemberships.loyaltyProgram',
            'currentMembership.loyaltyProgram',
        ]);

        $enrolledProgramIds = $customer->crmCustomerMemberships->pluck('loyalty_program_id')->filter()->unique()->values();
        $loyaltyProgramOptionsForEnroll = CrmLoyaltyProgram::query()
            ->forTenant($tenantId)
            ->where('status', 'active')
            ->when($enrolledProgramIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $enrolledProgramIds))
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (CrmLoyaltyProgram $p) => ['value' => (string) $p->id, 'label' => $p->code.' — '.$p->name])
            ->values()
            ->all();

        return view('sales.customers.show', [
            'customer' => $customer,
            'crmFollowUpTypeOptions' => CrmActivity::followUpTypesForModal(),
            'loyaltyProgramOptionsForEnroll' => $loyaltyProgramOptionsForEnroll,
        ]);
    }

    /**
     * تسجيل العميل في برنامج ولاء نشط (مرة واحدة لكل برنامج).
     */
    public function enrollLoyaltyProgram(Request $request, Customer $customer): RedirectResponse
    {
        $tenantId = (int) auth()->id();
        if ((int) $customer->user_id !== $tenantId) {
            abort(403);
        }

        $data = $request->validate([
            'loyalty_program_id' => [
                'required',
                'integer',
                Rule::exists('crm_loyalty_programs', 'id')->where(
                    fn ($q) => $q->where('user_id', $tenantId)->where('status', 'active')
                ),
            ],
            'start_date' => ['nullable', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $membership = CrmCustomerMembership::query()->updateOrCreate(
            [
                'user_id' => $tenantId,
                'customer_id' => $customer->id,
            ],
            [
                'loyalty_program_id' => (int) $data['loyalty_program_id'],
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'auto_renew' => $request->boolean('auto_renew', true),
                'notes' => $data['notes'] ?? null,
            ]
        );

        // Keep loyalty account available for points tracking.
        CrmLoyaltyAccount::query()->firstOrCreate(
            [
                'user_id' => $tenantId,
                'customer_id' => $customer->id,
                'loyalty_program_id' => $membership->loyalty_program_id,
            ],
            [
                'total_points' => 0,
                'used_points' => 0,
            ]
        );

        return redirect()
            ->route('sales.customers.show', $customer)
            ->with('success', 'تم تحديث اشتراك العميل على الخطة بنجاح.');
    }

    public function create(): View
    {
        $nextCode = Customer::generateNextCodeForUser((int) (auth()->id() ?? 1));

        return view('sales.customers.create', compact('nextCode'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => $request->filled('phone') ? $request->phone : null,
            'mobile' => $request->filled('mobile') ? $request->mobile : null,
            'vat_number' => $request->filled('vat_number') ? $request->vat_number : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'phone')->where('user_id', auth()->id())],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'vat_number')->where('user_id', auth()->id())],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'phone.unique' => 'هذا العميل (رقم الهاتف) مسجل مسبقاً في النظام.',
            'vat_number.unique' => 'هذا العميل (رقم ضريبي VAT) مسجل مسبقاً في النظام.',
        ]);

        $data['user_id'] = (int) (auth()->id() ?? 1);
        $data['code'] = Customer::generateNextCodeForUser($data['user_id']);
        $data['credit_limit'] = isset($data['credit_limit']) ? (float) $data['credit_limit'] : null;
        $data['tax_number'] = $data['vat_number'] ?? null;

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        try {
            $customer = Customer::create($data);
            $this->persistMorphAttachments($customer, $uploads, $data['user_id'], 'customers');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'تعذر حفظ العميل حالياً. يرجى المحاولة لاحقاً.');
        }

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'تم إضافة العميل بنجاح.');
    }

    public function edit(Customer $customer): View
    {
        $customer->load(['attachments']);

        return view('sales.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $request->merge([
            'phone' => $request->filled('phone') ? $request->phone : null,
            'mobile' => $request->filled('mobile') ? $request->mobile : null,
            'vat_number' => $request->filled('vat_number') ? $request->vat_number : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'phone')->where('user_id', auth()->id())->ignore($customer->id)],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'vat_number' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'vat_number')->where('user_id', auth()->id())->ignore($customer->id)],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'phone.unique' => 'هذا العميل (رقم الهاتف) مسجل مسبقاً في النظام.',
            'vat_number.unique' => 'هذا العميل (رقم ضريبي VAT) مسجل مسبقاً في النظام.',
        ]);

        $data['credit_limit'] = isset($data['credit_limit']) ? (float) $data['credit_limit'] : null;
        $data['tax_number'] = $data['vat_number'] ?? null;

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $customer->update($data);
        $this->persistMorphAttachments($customer, $uploads, (int) (auth()->id() ?? 1), 'customers');

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'تم تحديث بيانات العميل بنجاح.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'تم حذف العميل بنجاح.');
    }

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'Customer Code',
            'Customer Name',
            'Arabic Name',
            'Email',
            'Phone',
            'Mobile',
            'VAT Number',
            'Credit Limit',
            'Payment Terms (Days)',
            'Active',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customers-import-template.csv"',
        ]);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_CUSTOMERS);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.customers.index')
            ->with('success', "تم استيراد العملاء. نجاح: {$summary['created']} إضافة، {$summary['updated']} تحديث. فشل: {$summary['failed']}.")
            ->with('import_result', $summary);
    }
}
