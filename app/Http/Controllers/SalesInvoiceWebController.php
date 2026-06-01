<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Concerns\RespondsWithBusinessJsonErrors;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\EinvoiceSetting;
use App\Models\Item;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Models\Warehouse;
use App\Services\ExcelImportService;
use App\Services\InvoicePaymentRecordingService;
use App\Services\Sales\SalesInvoicePostingService;
use App\Services\Sales\SalesReceiptService;
use App\Services\ZatcaService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SalesInvoiceWebController extends Controller
{
    use ResolvesOperationsTenant;
    use RespondsWithBusinessJsonErrors;

    public function __construct(
        private readonly SalesInvoicePostingService $postingService,
        private readonly SalesReceiptService $receiptService,
    ) {}

    public function index(Request $request): View|Response
    {
        $query = SalesInvoice::with(['customer', 'warehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        $statusFilter = $request->get('status');
        if ($statusFilter && $statusFilter !== 'جميع الحالات') {
            $bal = '(sales_invoices.total - COALESCE(sales_invoices.paid_amount, 0))';
            $effectiveDue = 'DATE(COALESCE(sales_invoices.due_date, CASE WHEN sales_invoices.payment_method = \'cash\' THEN sales_invoices.date ELSE DATE_ADD(sales_invoices.date, INTERVAL 30 DAY) END))';
            if ($statusFilter === 'مدفوعة') {
                $query->whereRaw("{$bal} <= 0.0001");
            } elseif ($statusFilter === 'مسودة') {
                $query->where('invoice_status', 'draft');
            } elseif ($statusFilter === 'متأخرة') {
                $query->whereRaw("{$bal} > 0.0001")
                    ->whereRaw("{$effectiveDue} < CURDATE()");
            } elseif ($statusFilter === 'مستحق') {
                $query->whereRaw("{$bal} > 0.0001")
                    ->whereRaw("{$effectiveDue} >= CURDATE()");
            } elseif ($statusFilter === 'مدفوعة جزئياً') {
                $query->whereRaw("{$bal} > 0.0001")
                    ->whereRaw('COALESCE(sales_invoices.paid_amount, 0) > 0.0001')
                    ->whereRaw("{$effectiveDue} >= CURDATE()");
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم الفاتورة,العميل,التاريخ,الإجمالي,الحالة\n";
            foreach ($rows as $inv) {
                $row = $this->salesInvoiceListRow($inv);
                $csv .= '"SINV-'.$inv->id.'","'.str_replace('"', '""', $inv->customer?->name ?? '').'","'.($inv->date?->format('Y-m-d') ?? '').'",'.(float) $inv->total.',"'.$row->status."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="sales-invoices-'.date('Y-m-d').'.csv"',
            ]);
        }

        $invoices = $query->paginate(20)->withQueryString();

        $rows = $invoices->getCollection()->map(fn (SalesInvoice $inv) => $this->salesInvoiceListRow($inv));
        $invoices->setCollection($rows);

        $allInvoices = SalesInvoice::with('customer')->get();
        $totalInvoices = $allInvoices->count();
        $totalAmount = $allInvoices->sum('total');
        $dueAmount = 0;
        $overdueAmount = 0;
        foreach ($allInvoices as $inv) {
            $row = $this->salesInvoiceListRow($inv);
            if ($row->balance <= 0.0001) {
                continue;
            }
            if ($row->status === 'متأخرة') {
                $overdueAmount += $row->balance;
            } elseif (in_array($row->status, ['مستحق', 'مدفوعة جزئياً'], true)) {
                $dueAmount += $row->balance;
            }
        }

        $customers = Customer::where('is_active', true)->orderByRaw('COALESCE(name_ar, name)')->get();
        $statuses = ['جميع الحالات', 'مدفوعة', 'مسودة', 'مستحق', 'متأخرة', 'مدفوعة جزئياً'];

        $invoicePaymentMethodOptions = collect(SalesPayment::paymentMethodLabels())
            ->map(fn (string $label, string $key) => ['value' => $key, 'label' => $label])
            ->values()
            ->all();

        return view('sales.invoices.index', [
            'invoices' => $invoices,
            'totalInvoices' => $totalInvoices,
            'totalAmount' => $totalAmount,
            'dueAmount' => $dueAmount,
            'overdueAmount' => $overdueAmount,
            'customers' => $customers,
            'statuses' => $statuses,
            'invoicePaymentMethodOptions' => $invoicePaymentMethodOptions,
        ]);
    }

    public function recordPayment(Request $request, SalesInvoice $invoice): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,card'],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'amount.required' => 'أدخل مبلغ الدفعة.',
            'payment_method.required' => 'اختر وسيلة الدفع.',
        ]);

        try {
            $invoice->loadMissing('customer');
            $this->receiptService->record($uid, $invoice->customer, [
                'amount' => (float) $data['amount'],
                'date' => $data['date'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'sales_invoice_id' => $invoice->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', 'تم تسجيل الدفعة وتحديث الفاتورة وإنشاء القيد المحاسبي بنجاح.');
    }

    /**
     * @return object{
     *     id:int,
     *     invoice_number:string,
     *     customer_name:string,
     *     issue_date:?string,
     *     due_date:string,
     *     total:float,
     *     balance:float,
     *     status:string,
     *     invoice_status:string,
     *     record_payment_allowed:bool
     * }
     */
    private function salesInvoiceListRow(SalesInvoice $inv): object
    {
        $total = (float) $inv->total;
        $paid = (float) ($inv->paid_amount ?? 0);
        $balance = max(0, $total - $paid);
        $dueDate = $inv->due_date
            ? $inv->due_date->copy()->startOfDay()
            : ($inv->payment_method === 'cash'
                ? $inv->date->copy()->startOfDay()
                : $inv->date->copy()->addDays(30)->startOfDay());

        if ((string) ($inv->invoice_status ?? '') === 'draft') {
            $status = 'مسودة';
        } elseif ($balance <= 0.0001) {
            $status = 'مدفوعة';
        } elseif ($dueDate->isPast()) {
            $status = 'متأخرة';
        } elseif ($paid > 0.0001) {
            $status = 'مدفوعة جزئياً';
        } else {
            $status = 'مستحق';
        }

        return (object) [
            'id' => $inv->id,
            'invoice_number' => 'SINV-'.$inv->id,
            'customer_name' => $inv->customer?->name ?? '-',
            'issue_date' => $inv->date?->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'total' => $total,
            'balance' => $balance,
            'status' => $status,
            'invoice_status' => (string) ($inv->invoice_status ?? ''),
            'record_payment_allowed' => (string) ($inv->invoice_status ?? '') !== 'draft' && $balance > 0.0001,
        ];
    }

    public function create(Request $request): View
    {
        $warehouse = Warehouse::active()->orderBy('name_ar')->first();
        if (! $warehouse) {
            $wuid = (int) auth()->id();
            $warehouse = Warehouse::firstOrCreate(
                ['code' => 'MAIN', 'user_id' => $wuid],
                [
                    'name_ar' => 'المخزن الرئيسي',
                    'name_en' => 'Main Warehouse',
                    'is_active' => true,
                    'user_id' => $wuid,
                ]
            );
        }
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $items = Item::active()
            ->orderBy('code')
            ->get()
            ->map(fn (Item $i) => [
                'id' => $i->id,
                'code' => $i->code,
                'name_ar' => $i->name_ar,
                'name_en' => $i->name_en,
                'selling_price' => $i->selling_price,
                'sale_price' => $i->sale_price,
            ])
            ->values();

        $initialCustomerId = null;
        $initialDate = now()->format('Y-m-d');
        $initialDueDate = null;
        $initialLines = null;
        $fromQuotationId = null;
        $referenceFromQuotation = null;
        $fromSalesOrderId = null;
        $postingSource = SalesInvoice::POSTING_SOURCE_DIRECT;

        $salesOrderId = $request->integer('sales_order_id');
        if ($salesOrderId > 0) {
            $salesOrder = SalesOrder::with(['customer', 'items.item'])->find($salesOrderId);
            if ($salesOrder) {
                $fromSalesOrderId = $salesOrder->id;
                $postingSource = SalesInvoice::POSTING_SOURCE_ORDER;
                $initialCustomerId = $salesOrder->customer_id;
                $initialDate = $salesOrder->order_date?->format('Y-m-d') ?? now()->format('Y-m-d');
                $initialDueDate = $salesOrder->expected_delivery?->format('Y-m-d');
                $initialLines = $salesOrder->items->map(fn ($row) => [
                    'item_id' => $row->item_id,
                    'quantity' => (float) $row->quantity,
                    'unit_price' => (float) $row->unit_price,
                    'discount_percent' => (float) ($row->discount_percent ?? 0),
                    'tax_percent' => (float) ($row->tax_percent ?? 0),
                ])->toArray();
            }
        }

        $quotationId = $request->input('quotation_id', $request->input('from_quotation'));
        if (! empty($quotationId) && $fromSalesOrderId === null) {
            $quotation = Quotation::with(['customer', 'items.item'])->find($quotationId);
            if ($quotation && $quotation->status === \App\Models\Quotation::STATUS_APPROVED) {
                $fromQuotationId = $quotation->id;
                $referenceFromQuotation = 'من عرض السعر QT-'.str_pad((string) $quotation->id, 3, '0', STR_PAD_LEFT);
                $initialCustomerId = $quotation->customer_id;
                $initialDate = $quotation->date?->format('Y-m-d') ?? now()->format('Y-m-d');
                $initialDueDate = $quotation->valid_until?->format('Y-m-d');
                $initialLines = $quotation->items->map(fn ($row) => [
                    'item_id' => $row->item_id,
                    'quantity' => (float) $row->quantity,
                    'unit_price' => (float) $row->unit_price,
                    'discount_percent' => (float) $row->discount_percent,
                    'tax_percent' => (float) $row->tax_percent,
                ])->toArray();
            }
        }

        if ($initialCustomerId === null && $request->filled('customer_id')) {
            $cid = (int) $request->input('customer_id');
            if (Customer::where('user_id', (int) auth()->id())->whereKey($cid)->exists()) {
                $initialCustomerId = $cid;
            }
        }

        return view('sales.invoices.create', [
            'customers' => $customers,
            'warehouse' => $warehouse,
            'items' => $items,
            'initialCustomerId' => $initialCustomerId,
            'initialDate' => $initialDate,
            'initialDueDate' => $initialDueDate,
            'initialLines' => $initialLines,
            'fromQuotationId' => $fromQuotationId,
            'referenceFromQuotation' => $referenceFromQuotation,
            'fromSalesOrderId' => $fromSalesOrderId,
            'postingSource' => $postingSource,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('user_id', $uid)],
            'quotation_id' => ['nullable', Rule::exists('quotations', 'id')->where('user_id', $uid)],
            'sales_order_id' => ['nullable', Rule::exists('sales_orders', 'id')->where('user_id', $uid)],
            'posting_source' => ['nullable', 'in:order,direct'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'customer_id.required' => 'اختر العميل.',
            'customer_id.exists' => 'العميل المختار غير صالح.',
            'warehouse_id.required' => 'المستودع مطلوب.',
            'warehouse_id.exists' => 'المستودع غير صالح.',
            'date.required' => 'تاريخ الإصدار مطلوب.',
            'date.date' => 'تاريخ الإصدار غير صالح.',
            'due_date.required' => 'تاريخ الاستحقاق مطلوب.',
            'due_date.date' => 'تاريخ الاستحقاق غير صالح.',
            'due_date.after_or_equal' => 'تاريخ الاستحقاق يجب أن يكون في أو بعد تاريخ الإصدار.',
            'lines.required' => 'أضف بنداً واحداً على الأقل للفاتورة.',
            'lines.array' => 'بنود الفاتورة غير صالحة.',
            'lines.min' => 'أضف بنداً واحداً على الأقل للفاتورة.',
            'lines.*.item_id.required' => 'اختر المنتج لكل بند.',
            'lines.*.item_id.exists' => 'أحد المنتجات غير صالح.',
            'lines.*.quantity.required' => 'أدخل كمية لكل بند.',
            'lines.*.quantity.numeric' => 'الكمية يجب أن تكون رقماً.',
            'lines.*.quantity.min' => 'الكمية يجب أن تكون أكبر من صفر.',
            'lines.*.unit_price.required' => 'أدخل سعر الوحدة لكل بند.',
            'lines.*.unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقماً.',
            'lines.*.unit_price.gt' => 'سعر الوحدة يجب أن يكون أكبر من صفر.',
            'lines.*.discount_percent.numeric' => 'نسبة الخصم يجب أن تكون رقماً.',
            'lines.*.discount_percent.min' => 'نسبة الخصم لا يمكن أن تكون سالبة.',
            'lines.*.discount_percent.max' => 'نسبة الخصم لا تتجاوز 100.',
            'lines.*.tax_percent.numeric' => 'نسبة الضريبة يجب أن تكون رقماً.',
            'lines.*.tax_percent.min' => 'نسبة الضريبة لا يمكن أن تكون سالبة.',
            'lines.*.tax_percent.max' => 'نسبة الضريبة لا تتجاوز 100.',
        ]);
        $paymentMethod = $data['payment_method'] ?? 'credit';

        // منع إرسال مزدوج سريع لنفس المحتوى (نفس المستخدم خلال ثانيتين)
        $dedupeKey = 'sales_invoice_dedupe:'.auth()->id().':'.hash('sha256', json_encode($request->only([
            'customer_id', 'quotation_id', 'warehouse_id', 'date', 'due_date', 'lines',
        ])));
        if (! Cache::add($dedupeKey, true, now()->addSeconds(2))) {
            $dupMsg = 'تم استلام طلب مماثل مؤخراً. إن كانت العملية ناجحة ستجد الفاتورة في القائمة—لا تعِد الإرسال فوراً.';
            if ($request->expectsJson() || $request->wantsJson()) {
                session()->flash('info', $dupMsg);

                return response()->json(['redirect' => route('sales.invoices.index'), 'ok' => true]);
            }

            return redirect()
                ->route('sales.invoices.index', [], 303)
                ->with('info', $dupMsg);
        }

        if (! empty($data['quotation_id'])) {
            $qid = (int) $data['quotation_id'];
            $quotation = Quotation::query()->find($qid);
            if (! $quotation) {
                $this->businessForbidden($request, 'عرض السعر غير موجود.');
            }
            if ($quotation->status === Quotation::STATUS_CONVERTED_TO_ORDER) {
                if (SalesInvoice::query()->where('quotation_id', $qid)->exists()) {
                    $idemMsg = 'تم حفظ الفاتورة وتحويل عرض السعر مسبقاً. راجع قائمة الفواتير.';
                    if ($request->expectsJson() || $request->wantsJson()) {
                        session()->flash('info', $idemMsg);

                        return response()->json(['redirect' => route('sales.invoices.index'), 'ok' => true]);
                    }

                    return redirect()
                        ->route('sales.invoices.index', [], 303)
                        ->with('info', $idemMsg);
                }
                $this->businessForbidden($request, 'عرض السعر محوّل مسبقاً دون فاتورة مرتبطة؛ راجع البيانات أو الدعم.');
            }
            if ($quotation->status !== Quotation::STATUS_APPROVED) {
                $this->businessForbidden($request, 'لا يمكن تحويل عرض السعر إلا في حالة «معتمد». راجع حالة العرض من شاشة العروض.');
            }
        }

        if (! empty($data['quotation_id']) && empty($data['reference'])) {
            $data['reference'] = 'من عرض السعر QT-'.str_pad((string) $data['quotation_id'], 3, '0', STR_PAD_LEFT);
        }

        $tenantUserId = $this->resolveOperationsTenantUserId();
        $normalizedLines = $this->postingService->normalizeLines($tenantUserId, $data['lines']);

        if ($normalizedLines === []) {
            if ($request->expectsJson() || $request->wantsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.',
                ], 422));
            }

            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.');
        }

        try {
            $postingSource = ! empty($data['sales_order_id'])
                ? SalesInvoice::POSTING_SOURCE_ORDER
                : ($data['posting_source'] ?? SalesInvoice::POSTING_SOURCE_DIRECT);

            $invoice = $this->postingService->createAndPost($tenantUserId, [
                'customer_id' => (int) $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'posting_source' => $postingSource,
                'warehouse_id' => (int) $data['warehouse_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'payment_method' => $paymentMethod,
            ], $normalizedLines);
        } catch (RuntimeException|\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->wantsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => $e->getMessage(),
                ], 422));
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        if (! empty($data['quotation_id'])) {
            Quotation::where('id', $data['quotation_id'])->update(['status' => Quotation::STATUS_CONVERTED_TO_ORDER]);
        }

        if ($invoice instanceof SalesInvoice) {
            $this->persistZatcaMetadataForNewInvoice($invoice);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            session()->flash('success', 'تم حفظ البيانات بنجاح!');

            return response()->json(['redirect' => route('sales.invoices.index'), 'ok' => true]);
        }

        return redirect()
            ->route('sales.invoices.index', [], 303)
            ->with('success', 'تم حفظ البيانات بنجاح!');
    }

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'reference',
            'customer_code',
            'warehouse_code',
            'date',
            'due_date',
            'item_code',
            'quantity',
            'unit_price',
            'discount',
            'tax_percent',
            'notes',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sales-invoices-import-template.csv"',
        ]);
    }

    public function import(Request $request, ExcelImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $invoicesData = [];

        try {
            $summary = DB::transaction(function () use ($request, $importService, &$invoicesData) {
                $importService->importSimple(
                    $request->file('file'),
                    ['reference', 'customer_code', 'warehouse_code', 'date', 'item_code', 'quantity', 'unit_price'],
                    function (array $row, int $line) use (&$invoicesData) {
                        $reference = $row['reference'] ?? null;
                        $customerCode = $row['customer_code'] ?? null;
                        $warehouseCode = $row['warehouse_code'] ?? null;
                        $date = $row['date'] ?? null;
                        $itemCode = $row['item_code'] ?? null;

                        if (! $reference || ! $customerCode || ! $warehouseCode || ! $date || ! $itemCode) {
                            throw new \RuntimeException(
                                'بعض الحقول الرئيسية مفقودة في السطر رقم '.$line.' (reference, customer_code, warehouse_code, date, item_code).'
                            );
                        }

                        $customer = Customer::where('code', $customerCode)->first();
                        if (! $customer) {
                            throw new \RuntimeException(
                                'تعذر العثور على العميل بالكود '.$customerCode.' في السطر رقم '.$line.'.'
                            );
                        }

                        $warehouse = Warehouse::where('code', $warehouseCode)->first();
                        if (! $warehouse) {
                            throw new \RuntimeException(
                                'تعذر العثور على المستودع بالكود '.$warehouseCode.' في السطر رقم '.$line.'.'
                            );
                        }

                        $item = Item::where('code', $itemCode)->first();
                        if (! $item) {
                            throw new \RuntimeException(
                                'تعذر العثور على الصنف بالكود '.$itemCode.' في السطر رقم '.$line.'.'
                            );
                        }

                        $quantity = (float) ($row['quantity'] ?? 0);
                        $unitPrice = (float) ($row['unit_price'] ?? 0);
                        $discount = (float) ($row['discount'] ?? 0);
                        $taxPercent = $row['tax_percent'] !== '' ? (float) $row['tax_percent'] : 15.0;

                        if ($quantity <= 0) {
                            throw new \RuntimeException(
                                'الكمية يجب أن تكون أكبر من صفر في السطر رقم '.$line.'.'
                            );
                        }

                        $key = $reference.'|'.$customer->id;

                        $dueDateCell = trim((string) ($row['due_date'] ?? ''));

                        if (! isset($invoicesData[$key])) {
                            if ($dueDateCell === '') {
                                throw new \RuntimeException(
                                    'تاريخ الاستحقاق مطلوب في السطر رقم '.$line.' (أول سطر لكل مرجع فاتورة).'
                                );
                            }
                            if (SalesInvoice::where('reference', $reference)->where('customer_id', $customer->id)->exists()) {
                                throw new \RuntimeException(
                                    'توجد بالفعل فاتورة مبيعات بنفس المرجع للعميل (reference='.$reference.') – السطر رقم '.$line.'.'
                                );
                            }

                            $invoicesData[$key] = [
                                'customer' => $customer,
                                'warehouse' => $warehouse,
                                'header' => [
                                    'reference' => $reference,
                                    'date' => $date,
                                    'due_date' => $dueDateCell,
                                    'notes' => $row['notes'] ?: null,
                                ],
                                'lines' => [],
                            ];
                        } else {
                            // تأكيد اتساق رأس الفاتورة داخل نفس المرجع
                            $existing = $invoicesData[$key]['header'];
                            if ($existing['date'] !== $date) {
                                throw new \RuntimeException(
                                    'تاريخ الفاتورة غير متسق لنفس المرجع '.$reference.' في السطر رقم '.$line.'.'
                                );
                            }
                            if ($dueDateCell !== '' && $dueDateCell !== $existing['due_date']) {
                                throw new \RuntimeException(
                                    'تاريخ الاستحقاق غير متسق لنفس المرجع '.$reference.' في السطر رقم '.$line.'.'
                                );
                            }
                        }

                        $invoicesData[$key]['lines'][] = [
                            'item' => $item,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount' => $discount,
                            'tax_percent' => $taxPercent,
                        ];

                        return 'created';
                    }
                );

                $created = 0;
                $tenantUserId = $this->resolveOperationsTenantUserId();

                foreach ($invoicesData as $data) {
                    $customer = $data['customer'];
                    $warehouse = $data['warehouse'];
                    $header = $data['header'];

                    $importLines = collect($data['lines'])
                        ->map(fn (array $line): array => [
                            'item_id' => (int) $line['item']->id,
                            'quantity' => (float) $line['quantity'],
                            'unit_price' => (float) $line['unit_price'],
                            'discount' => (float) $line['discount'],
                            'tax_percent' => (float) $line['tax_percent'],
                        ])
                        ->filter(fn (array $l) => $l['quantity'] > 0)
                        ->values()
                        ->all();

                    if ($importLines === []) {
                        continue;
                    }

                    $invoice = $this->postingService->createAndPost($tenantUserId, [
                        'customer_id' => $customer->id,
                        'warehouse_id' => $warehouse->id,
                        'date' => $header['date'],
                        'due_date' => $header['due_date'],
                        'reference' => $header['reference'],
                        'notes' => $header['notes'] ?? null,
                        'payment_method' => SalesInvoice::PAYMENT_CREDIT,
                        'posting_source' => SalesInvoice::POSTING_SOURCE_DIRECT,
                    ], $importLines);

                    $this->persistZatcaMetadataForNewInvoice($invoice);
                    $created++;
                }

                return ['created' => $created, 'updated' => 0];
            });
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.invoices.index')
            ->with('success', 'تم استيراد فواتير المبيعات وترحيلها بنجاح (مخزون + إيراد + COGS). تم إنشاء '.($summary['created'] ?? 0).' فاتورة.');
    }

    public function print(SalesInvoice $invoice): View
    {
        $invoice->load(['customer', 'items.item']);
        $company = CompanySetting::forTenant((int) $invoice->user_id);

        return view('sales.invoices.print', compact('invoice', 'company'));
    }

    /**
     * بعد حفظ الفاتورة: توليد UBL/UUID عبر ZatcaService وحفظ ICV وسلسلة PIH/التجزئة، أو تعليم الفشل دون التراجع عن الفاتورة.
     */
    private function persistZatcaMetadataForNewInvoice(SalesInvoice $invoice): void
    {
        $userId = (int) $invoice->user_id;

        $lastIcv = SalesInvoice::query()
            ->where('user_id', $userId)
            ->where('id', '<', $invoice->id)
            ->max('zatca_icv');
        $nextIcv = ((int) ($lastIcv ?? 0)) + 1;

        $lastSuccessful = SalesInvoice::query()
            ->where('user_id', $userId)
            ->where('id', '<', $invoice->id)
            ->whereNotNull('zatca_hash')
            ->where(function ($q) {
                $q->whereNull('zatca_status')->orWhere('zatca_status', '<>', 'failed');
            })
            ->orderByDesc('id')
            ->first();

        $pih = $lastSuccessful?->zatca_hash ?? ZatcaService::DEFAULT_FIRST_PREVIOUS_INVOICE_HASH_BASE64;

        try {
            $invoice->loadMissing(['customer', 'items.item']);
            $setting = EinvoiceSetting::get();
            $zatca = app(ZatcaService::class);
            $result = $zatca->mapSalesInvoiceToUbl21Xml(
                $invoice,
                $setting,
                $nextIcv,
                $pih,
                CompanySetting::forTenant((int) $invoice->user_id),
            );

            $invoice->forceFill([
                'zatca_invoice_uuid' => $result['invoice_uuid'],
                'zatca_hash' => $result['unsigned_invoice_hash_base64'],
                'zatca_icv' => $nextIcv,
                'zatca_pih' => $pih,
                'zatca_signed_xml' => null,
                'zatca_status' => 'generated',
            ])->save();
        } catch (\Throwable $e) {
            Log::error('ZATCA invoice metadata generation failed', [
                'sales_invoice_id' => $invoice->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            try {
                $invoice->forceFill([
                    'zatca_status' => 'failed',
                ])->save();
            } catch (\Throwable $inner) {
                Log::error('Failed to persist ZATCA failed status on sales invoice', [
                    'sales_invoice_id' => $invoice->id,
                    'message' => $inner->getMessage(),
                    'exception' => $inner,
                ]);
            }
        }
    }
}
