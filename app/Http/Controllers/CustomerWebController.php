<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Models\Customer;
use App\Services\UniversalImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerWebController extends Controller
{
    use PersistsMorphAttachments;

    public function index(Request $request): View|Response
    {
        $searchTerm = trim((string) $request->input('q', ''));
        $hasSearch = $searchTerm !== '';
        $query = Customer::orderBy('code');

        if ($hasSearch) {
            $query->where(function ($qry) use ($searchTerm) {
                $qry->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('name_ar', 'like', "%{$searchTerm}%")
                    ->orWhere('code', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('vat_number', 'like', "%{$searchTerm}%")
                    ->orWhere('tax_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "الرمز,الاسم,البريد,الهاتف,رقم ضريبي VAT,الحد الائتماني,أيام السداد,الحالة\n";
            foreach ($rows as $c) {
                $rawStatus = $c->status ?? ($c->is_active ? 'active' : 'inactive');
                $statusLabel = $rawStatus === 'active' ? 'نشط' : 'غير نشط';
                $vat = $c->vat_number ?? $c->tax_number ?? '';
                $credit = $c->credit_limit !== null ? (string) $c->credit_limit : '';
                $terms = $c->payment_terms_days !== null ? (string) $c->payment_terms_days : '';
                $csv .= '"'.str_replace('"', '""', $c->code ?? '').'","'.str_replace('"', '""', $c->display_name ?? '').'","'.str_replace('"', '""', $c->email ?? '').'","'.str_replace('"', '""', $c->phone ?? '').'","'.str_replace('"', '""', $vat).'","'.$credit.'","'.$terms.'","'.$statusLabel."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="customers-'.date('Y-m-d').'.csv"',
            ]);
        }

        $customers = $query->paginate(20)->withQueryString();

        $rows = $customers->getCollection()->map(function ($customer) {
            return (object) [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->display_name,
                'name_ar' => $customer->name_ar,
                'email' => $customer->email ?? '-',
                'phone' => $customer->phone ?? '-',
                'vat_number' => $customer->vat_number ?? $customer->tax_number,
                'credit_limit' => $customer->credit_limit,
                'payment_terms_days' => $customer->payment_terms_days,
                'balance' => 0,
                'status' => (($customer->status ?? ($customer->is_active ? 'active' : 'inactive')) === 'active')
                    ? 'active'
                    : 'inactive',
            ];
        });

        if ($rows->isEmpty()) {
            // عند البحث لا نعرض العملاء التجريبيين حتى لا يظهر وكأن النتائج مطابقة للاستعلام
            if (! $hasSearch) {
                $dummies = [
                    (object) ['id' => null, 'code' => 'C001', 'name' => 'عميل تجريبي ١', 'email' => 'customer1@example.com', 'phone' => '0500000001', 'vat_number' => null, 'credit_limit' => null, 'payment_terms_days' => null, 'balance' => 0, 'status' => 'active'],
                    (object) ['id' => null, 'code' => 'C002', 'name' => 'عميل تجريبي ٢', 'email' => 'customer2@example.com', 'phone' => '0500000002', 'vat_number' => null, 'credit_limit' => null, 'payment_terms_days' => null, 'balance' => 1250.50, 'status' => 'active'],
                    (object) ['id' => null, 'code' => 'C003', 'name' => 'عميل تجريبي ٣', 'email' => 'customer3@example.com', 'phone' => '0500000003', 'vat_number' => null, 'credit_limit' => null, 'payment_terms_days' => null, 'balance' => 0, 'status' => 'active'],
                    (object) ['id' => null, 'code' => 'C004', 'name' => 'عميل تجريبي ٤', 'email' => 'customer4@example.com', 'phone' => '0500000004', 'vat_number' => null, 'credit_limit' => null, 'payment_terms_days' => null, 'balance' => 580.00, 'status' => 'inactive'],
                    (object) ['id' => null, 'code' => 'C005', 'name' => 'عميل تجريبي ٥', 'email' => 'customer5@example.com', 'phone' => '0500000005', 'vat_number' => null, 'credit_limit' => null, 'payment_terms_days' => null, 'balance' => 0, 'status' => 'active'],
                ];
                $customers = new LengthAwarePaginator(collect($dummies), 5, 5);
            } else {
                $customers->setCollection($rows);
            }
        } else {
            $customers->setCollection($rows);
        }

        return view('sales.customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['attachments']);

        return view('sales.customers.show', compact('customer'));
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
