<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\SalesPayment;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ExcelImportService;
use App\Services\Purchasing\PurchaseInvoicePostingService;
use App\Services\Purchasing\SupplierPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PurchaseInvoiceWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly PurchaseInvoicePostingService $postingService,
        private readonly SupplierPaymentService $supplierPayments,
    ) {}

    public function index(Request $request): View|Response
    {
        $query = PurchaseInvoice::with(['supplier', 'warehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->input('supplier_id'));
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم الفاتورة,المورد,التاريخ,الاستحقاق,الإجمالي,المدفوع,الرصيد\n";
            foreach ($rows as $inv) {
                $balance = max(0, (float) $inv->total - (float) $inv->paid_amount);
                $csv .= '"'.str_replace('"', '""', $inv->reference ?: $inv->id).'","'.str_replace('"', '""', $inv->supplier?->name ?? '').'","'.($inv->date?->format('Y-m-d') ?? '').'","'.($inv->due_date?->format('Y-m-d') ?? '').'",'.(float) $inv->total.','.(float) $inv->paid_amount.','.$balance."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="purchase-invoices-'.date('Y-m-d').'.csv"',
            ]);
        }

        $invoices = $query->paginate(20)->withQueryString();

        $totalDue = (float) PurchaseInvoice::selectRaw('SUM(total - COALESCE(paid_amount, 0)) as balance')
            ->whereRaw('(total - COALESCE(paid_amount, 0)) > 0')
            ->value('balance');
        $overdueQuery = PurchaseInvoice::whereRaw('(total - COALESCE(paid_amount, 0)) > 0')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay());
        $overdueAmount = (float) (clone $overdueQuery)->selectRaw('SUM(total - COALESCE(paid_amount, 0)) as amt')->value('amt');
        $overdueCount = (int) (clone $overdueQuery)->count();
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $dueThisWeek = (float) PurchaseInvoice::whereRaw('(total - COALESCE(paid_amount, 0)) > 0')
            ->whereBetween('due_date', [$weekStart, $weekEnd])
            ->selectRaw('SUM(total - COALESCE(paid_amount, 0)) as amt')
            ->value('amt');
        $totalPaid = (float) PurchaseInvoice::sum('paid_amount');

        $suppliers = Supplier::query()
            ->where(function ($sub) {
                $sub->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar']);

        $invoicePaymentMethodOptions = collect(SalesPayment::paymentMethodLabels())
            ->map(fn (string $label, string $key) => ['value' => $key, 'label' => $label])
            ->values()
            ->all();

        return view('purchases.invoices.index', compact(
            'invoices',
            'totalDue',
            'overdueAmount',
            'overdueCount',
            'dueThisWeek',
            'totalPaid',
            'suppliers',
            'invoicePaymentMethodOptions'
        ));
    }

    public function recordPayment(Request $request, PurchaseInvoice $invoice): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,card'],
            'reference' => ['nullable', 'string', 'max:50'],
        ], [
            'amount.required' => 'أدخل مبلغ الدفعة.',
            'payment_method.required' => 'اختر وسيلة الدفع.',
        ]);

        try {
            $this->supplierPayments->record($tenantUserId, $invoice->supplier, [
                'amount' => (float) $data['amount'],
                'date' => $data['date'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'purchase_invoice_id' => $invoice->id,
            ]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.invoices.index')
            ->with('success', 'تم تسجيل الدفعة وإنشاء القيد المحاسبي بنجاح.');
    }

    public function create(Request $request): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name_ar')->get();
        $items = Item::active()->orderBy('code')->get();

        $fromPurchaseOrderId = null;
        $postingSource = PurchaseInvoice::POSTING_SOURCE_DIRECT;
        $initialSupplierId = null;
        $initialLines = null;

        $poId = $request->integer('purchase_order_id');
        if ($poId > 0) {
            $po = PurchaseOrder::with(['supplier', 'items.item'])->find($poId);
            if ($po) {
                $fromPurchaseOrderId = $po->id;
                $postingSource = PurchaseInvoice::POSTING_SOURCE_ORDER;
                $initialSupplierId = $po->supplier_id;
                $initialLines = $po->items->map(fn ($row) => [
                    'item_id' => $row->item_id,
                    'quantity' => (float) $row->quantity,
                    'unit_price' => (float) $row->unit_price,
                    'discount' => 0,
                    'vat_percent' => 15,
                ])->toArray();
            }
        }

        return view('purchases.invoices.create', compact(
            'suppliers',
            'warehouses',
            'items',
            'fromPurchaseOrderId',
            'postingSource',
            'initialSupplierId',
            'initialLines',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $tenantUserId)],
            'purchase_order_id' => ['nullable', Rule::exists('purchase_orders', 'id')->where('user_id', $tenantUserId)],
            'posting_source' => ['nullable', 'in:order,direct'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $tenantUserId)],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'currency' => ['nullable', 'string', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $tenantUserId)],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'supplier_id.required' => 'المورد مطلوب.',
            'date.required' => 'تاريخ الفاتورة مطلوب.',
            'due_date.required' => 'تاريخ الاستحقاق مطلوب.',
            'lines.required' => 'يجب إضافة بند واحد على الأقل.',
        ]);

        $lines = $this->postingService->normalizeLines($data['lines']);
        if ($lines === []) {
            return back()->withInput()->with('error', 'يجب إضافة على الأقل بند واحد بقيمة صحيحة.');
        }

        try {
            $postingSource = ! empty($data['purchase_order_id'])
                ? PurchaseInvoice::POSTING_SOURCE_ORDER
                : ($data['posting_source'] ?? PurchaseInvoice::POSTING_SOURCE_DIRECT);

            $this->postingService->createAndPost($tenantUserId, [
                'supplier_id' => (int) $data['supplier_id'],
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'posting_source' => $postingSource,
                'warehouse_id' => (int) $data['warehouse_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'],
                'reference' => $data['reference'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'currency' => $data['currency'] ?? 'SAR',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ], $lines);
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.invoices.index')
            ->with('success', 'تم حفظ فاتورة الشراء وترحيلها: تحديث المخزون ومتوسط التكلفة والقيد المحاسبي.');
    }

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'reference',
            'supplier_code',
            'warehouse_code',
            'date',
            'due_date',
            'currency',
            'item_code',
            'description',
            'quantity',
            'unit_price',
            'discount',
            'vat_percent',
            'notes',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="purchase-invoices-import-template.csv"',
        ]);
    }

    public function import(Request $request, ExcelImportService $importService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        try {
            $invoiceIdsToPost = [];

            $summary = DB::transaction(function () use ($request, $importService, $tenantUserId, &$invoiceIdsToPost) {
                return $importService->importSimple(
                    $request->file('file'),
                    ['reference', 'supplier_code', 'warehouse_code', 'date', 'item_code', 'quantity', 'unit_price'],
                    function (array $row, int $line) use ($tenantUserId, &$invoiceIdsToPost) {
                        $reference = $row['reference'] ?? null;
                        $supplierCode = $row['supplier_code'] ?? null;
                        $warehouseCode = $row['warehouse_code'] ?? null;
                        $date = $row['date'] ?? null;
                        $itemCode = $row['item_code'] ?? null;

                        if (! $reference || ! $supplierCode || ! $warehouseCode || ! $date || ! $itemCode) {
                            throw new RuntimeException("بعض الحقول الرئيسية مفقودة في السطر رقم {$line} (reference, supplier_code, warehouse_code, date, item_code).");
                        }

                        $supplier = Supplier::where('code', $supplierCode)->first();
                        if (! $supplier) {
                            throw new RuntimeException("تعذر العثور على المورد بالكود {$supplierCode} في السطر رقم {$line}.");
                        }

                        $warehouse = Warehouse::where('code', $warehouseCode)->first();
                        if (! $warehouse) {
                            throw new RuntimeException("تعذر العثور على المستودع بالكود {$warehouseCode} في السطر رقم {$line}.");
                        }

                        $item = Item::where('code', $itemCode)->first();
                        if (! $item) {
                            throw new RuntimeException("تعذر العثور على الصنف بالكود {$itemCode} في السطر رقم {$line}.");
                        }

                        $quantity = (float) ($row['quantity'] ?? 0);
                        $unitPrice = (float) ($row['unit_price'] ?? 0);
                        $discount = (float) ($row['discount'] ?? 0);
                        $vatPercent = $row['vat_percent'] !== '' ? (float) $row['vat_percent'] : 15.0;

                        if ($quantity <= 0) {
                            throw new RuntimeException("الكمية يجب أن تكون أكبر من صفر في السطر رقم {$line}.");
                        }

                        $lineNet = $quantity * $unitPrice - $discount;
                        $lineVat = $lineNet * $vatPercent / 100;
                        $lineTotal = $lineNet + $lineVat;

                        $invoice = PurchaseInvoice::where('reference', $reference)
                            ->where('supplier_id', $supplier->id)
                            ->first();

                        if (! $invoice) {
                            $dueDateImport = trim((string) ($row['due_date'] ?? ''));
                            if ($dueDateImport === '') {
                                throw new RuntimeException("تاريخ الاستحقاق مطلوب في السطر رقم {$line} (أول سطر لكل مرجع فاتورة).");
                            }
                            $invoice = PurchaseInvoice::create([
                                'user_id' => $tenantUserId,
                                'supplier_id' => $supplier->id,
                                'warehouse_id' => $warehouse->id,
                                'date' => $date,
                                'due_date' => $dueDateImport,
                                'reference' => $reference,
                                'supplier_invoice_number' => null,
                                'currency' => $row['currency'] ?: 'SAR',
                                'vat_rate' => $vatPercent,
                                'vat_amount' => 0,
                                'subtotal' => 0,
                                'total' => 0,
                                'status' => PurchaseInvoice::STATUS_DRAFT,
                                'notes' => $row['notes'] ?: null,
                                'internal_notes' => null,
                            ]);
                        }

                        $itemData = [
                            'user_id' => $tenantUserId,
                            'item_id' => $item->id,
                            'description' => $row['description'] ?: null,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'discount' => $discount,
                            'vat_percent' => $vatPercent,
                            'line_total' => $lineTotal,
                        ];

                        $existingLine = $invoice->items()
                            ->where('item_id', $item->id)
                            ->where('description', $itemData['description'])
                            ->first();

                        if ($existingLine) {
                            $existingLine->update($itemData);
                            $action = 'updated';
                        } else {
                            $invoice->items()->create($itemData);
                            $action = 'created';
                        }

                        $invoice->load('items');
                        $totals = $this->postingService->calculateTotals(
                            $invoice->items->map(fn (PurchaseInvoiceItem $l) => [
                                'item_id' => $l->item_id,
                                'quantity' => (float) $l->quantity,
                                'unit_price' => (float) $l->unit_price,
                                'discount' => (float) ($l->discount ?? 0),
                                'vat_percent' => (float) ($l->vat_percent ?? 15),
                            ])->all()
                        );

                        $invoice->update([
                            'subtotal' => $totals['subtotal'],
                            'vat_rate' => $totals['avg_vat_rate'],
                            'vat_amount' => $totals['vat_amount'],
                            'total' => $totals['grand_total'],
                        ]);

                        if (! $invoice->isPosted()) {
                            $invoiceIdsToPost[$invoice->id] = true;
                        }

                        return $action;
                    }
                );
            });

            foreach (array_keys($invoiceIdsToPost) as $invoiceId) {
                $invoice = PurchaseInvoice::query()->find($invoiceId);
                if ($invoice && ! $invoice->isPosted()) {
                    $this->postingService->postExisting($invoice);
                }
            }
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.invoices.index')
            ->with('success', "تم استيراد فواتير الموردين وترحيلها بنجاح. تمت إضافة {$summary['created']} وتحديث {$summary['updated']}.");
    }
}
