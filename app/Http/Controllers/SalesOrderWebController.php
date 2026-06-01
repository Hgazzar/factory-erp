<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Controllers\Concerns\RespondsWithBusinessJsonErrors;
use App\Models\AuditTrail;
use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\UniversalImportService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesOrderWebController extends Controller
{
    use PersistsMorphAttachments;
    use RespondsWithBusinessJsonErrors;

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'Order Number',
            'Customer Code',
            'Order Date',
            'Status',
            'Product Code',
            'Quantity',
            'Unit Price',
            'V A T Amount',
            'Total Amount',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sales-orders-import-template.csv"',
        ]);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_SALES_ORDERS);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.orders.index')
            ->with('success', "تم استيراد أوامر البيع. نجاح: {$summary['created']} إضافة، {$summary['updated']} تحديث. فشل: {$summary['failed']}.")
            ->with('import_result', $summary);
    }

    public function create(Request $request): View
    {
        $customers = Customer::where('is_active', true)->orderByRaw('COALESCE(name_ar, name)')->get();
        $quotations = Quotation::with('customer')
            ->where('status', Quotation::STATUS_APPROVED)
            ->orderByDesc('date')
            ->get();
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
        $warehouses = Warehouse::active()->orderBy('name_ar')->get(['id', 'name_ar', 'code']);

        $initialQuotationId = null;
        $initialCustomerId = null;
        $initialOrderDate = now()->format('Y-m-d');
        $initialExpectedDelivery = null;
        $initialLines = null;

        if ($request->filled('quotation_id')) {
            $quotation = Quotation::with(['customer', 'items.item'])
                ->where('status', Quotation::STATUS_APPROVED)
                ->find($request->integer('quotation_id'));

            if ($quotation) {
                $initialQuotationId = $quotation->id;
                $initialCustomerId = $quotation->customer_id;
                $initialOrderDate = $quotation->date?->format('Y-m-d') ?? $initialOrderDate;
                $initialExpectedDelivery = $quotation->valid_until?->format('Y-m-d');
                $initialLines = $quotation->items->map(function ($row) {
                    return [
                        'item_id' => $row->item_id,
                        'description' => '',
                        'quantity' => (float) $row->quantity,
                        'unit_price' => (float) $row->unit_price,
                        'discount_percent' => (float) $row->discount_percent,
                        'tax_percent' => (float) $row->tax_percent,
                        'warehouse_id' => '',
                    ];
                })->toArray();
            }
        }

        $nextOrderNumber = SalesOrder::generateNextOrderNumberForUser((int) (auth()->id() ?? 1));

        return view('sales.orders.create', compact(
            'customers',
            'quotations',
            'items',
            'warehouses',
            'initialQuotationId',
            'initialCustomerId',
            'initialOrderDate',
            'initialExpectedDelivery',
            'initialLines',
            'nextOrderNumber'
        ));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge([
            'lines' => collect($request->input('lines', []))->map(function ($line) {
                if (! is_array($line)) {
                    return $line;
                }
                if (array_key_exists('warehouse_id', $line) && ($line['warehouse_id'] === '' || $line['warehouse_id'] === null)) {
                    $line['warehouse_id'] = null;
                }

                return $line;
            })->all(),
        ]);

        $data = $request->validate([
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'order_date' => ['required', 'date'],
            'expected_delivery' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    try {
                        $delivery = Carbon::parse($value)->startOfDay();
                    } catch (\Throwable $e) {
                        return;
                    }
                    $orderDateRaw = $request->input('order_date');
                    if ($orderDateRaw !== null && $orderDateRaw !== '') {
                        try {
                            $order = Carbon::parse($orderDateRaw)->startOfDay();
                            if ($delivery->lt($order)) {
                                $fail('تاريخ التسليم المتوقع ('.$delivery->toDateString().') يجب ألا يكون قبل تاريخ الأمر ('.$order->toDateString().'). إذا غيّرت تاريخ الأمر، حدّث «التسليم المتوقع» ليتوافق معه.');
                            }
                        } catch (\Throwable $e) {
                            // يترك حقل تاريخ الأمر لقواعد التحقق الأخرى
                        }
                    }
                    if ($delivery->lt(Carbon::today())) {
                        $fail('تاريخ التسليم المتوقع ('.$delivery->toDateString().') في الماضي مقارنةً بتاريخ اليوم ('.Carbon::today()->toDateString().'). عند التحويل من عرض سعر قديم، حدّث «التسليم المتوقع» (و«تاريخ الأمر» إن لزم) لتاريخ اليوم أو لاحق.');
                    }
                },
            ],
            'reference' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ]);

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $dedupeKey = 'sales_order_dedupe:'.auth()->id().':'.hash('sha256', json_encode($request->only([
            'customer_id', 'quotation_id', 'order_date', 'expected_delivery', 'lines',
        ])));
        if (! Cache::add($dedupeKey, true, now()->addSeconds(2))) {
            $dupMsg = 'تم استلام طلب مماثل مؤخراً. إن كانت العملية ناجحة ستجد الأمر في القائمة—لا تعِد الإرسال فوراً.';
            if ($request->expectsJson() || $request->wantsJson()) {
                session()->flash('info', $dupMsg);

                return response()->json(['redirect' => route('sales.orders.index'), 'ok' => true]);
            }

            return redirect()->route('sales.orders.index')->with('info', $dupMsg);
        }

        if (! empty($data['quotation_id'])) {
            $qid = (int) $data['quotation_id'];
            $quotation = Quotation::query()->find($qid);
            if (! $quotation) {
                $this->businessForbidden($request, 'عرض السعر غير موجود.');
            }
            if ($quotation->status === Quotation::STATUS_CONVERTED_TO_ORDER) {
                if (SalesOrder::query()->where('quotation_id', $qid)->exists()) {
                    $idemMsg = 'تم حفظ أمر البيع وتحويل عرض السعر مسبقاً. راجع قائمة الأوامر.';
                    if ($request->expectsJson() || $request->wantsJson()) {
                        session()->flash('info', $idemMsg);

                        return response()->json(['redirect' => route('sales.orders.index'), 'ok' => true]);
                    }

                    return redirect()->route('sales.orders.index')->with('info', $idemMsg);
                }
                $this->businessForbidden($request, 'عرض السعر محوّل مسبقاً دون أمر بيع مرتبط؛ راجع البيانات أو الدعم.');
            }
            if ($quotation->status !== Quotation::STATUS_APPROVED) {
                $this->businessForbidden($request, 'لا يمكن تحويل عرض السعر إلا في حالة «معتمد». راجع حالة العرض من شاشة العروض.');
            }
        }

        $lines = collect($data['lines'])->map(function ($line) {
            $qty = (float) $line['quantity'];
            $price = (float) $line['unit_price'];
            $discount = (float) ($line['discount_percent'] ?? 0);
            $tax = (float) ($line['tax_percent'] ?? 0);
            $lineNet = $qty * $price * (1 - $discount / 100);
            $lineTax = $lineNet * $tax / 100;
            $lineTotal = $lineNet + $lineTax;

            return [
                'item_id' => (int) $line['item_id'],
                'warehouse_id' => ! empty($line['warehouse_id']) ? (int) $line['warehouse_id'] : null,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => $discount,
                'tax_percent' => $tax,
                'line_total' => round($lineTotal, 4),
                'description' => $line['description'] ?? null,
            ];
        })->filter(fn ($l) => $l['quantity'] > 0)->values();

        if ($lines->isEmpty()) {
            if ($request->expectsJson() || $request->wantsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.',
                ], 422));
            }

            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.');
        }

        $defaultWarehouseId = Warehouse::active()->orderByDesc('id')->value('id');
        $lines = $lines->map(function (array $line) use ($defaultWarehouseId) {
            if ($line['warehouse_id'] !== null) {
                return $line;
            }

            $line['warehouse_id'] = $this->resolveWarehouseIdForOrderLine(
                (int) $line['item_id'],
                (float) $line['quantity'],
                $defaultWarehouseId !== null ? (int) $defaultWarehouseId : null
            );

            return $line;
        });

        foreach ($lines as $line) {
            $wid = $line['warehouse_id'] ?? null;
            if ($wid === null || (int) $wid === 0) {
                $this->businessUnprocessable($request, 'تعذر تحديد المستودع لهذا البند. اختر مستودعاً من القائمة، أو فعّل مستودعاً في النظام واربط الصنف به مع رصيد كافٍ.');
            }
        }

        $total = $lines->sum('line_total');

        $order = null;
        $uid = (int) (auth()->id() ?? 1);
        DB::transaction(function () use ($request, $data, $lines, $total, $uid, &$order, $uploads) {
            // خصم المخزون والقيود المحاسبية تتم حصرياً عند ترحيل فاتورة المبيعات (Anti-Duplication).
            $order = SalesOrder::create([
                'user_id' => $uid,
                'order_number' => SalesOrder::generateNextOrderNumberForUser($uid),
                'customer_id' => $data['customer_id'],
                'quotation_id' => $data['quotation_id'] ?? null,
                'order_date' => $data['order_date'],
                'expected_delivery' => $data['expected_delivery'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => SalesOrder::STATUS_PENDING,
                'total' => $total,
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            if (! empty($data['quotation_id'])) {
                Quotation::where('id', $data['quotation_id'])
                    ->update(['status' => Quotation::STATUS_CONVERTED_TO_ORDER]);
            }

            $this->persistMorphAttachments($order, $uploads, $uid, 'sales-orders');
        });

        $order->load('customer');
        AuditTrail::log('create', 'sales_orders', $order->id, null, [
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer?->name,
            'order_date' => $order->order_date?->format('Y-m-d'),
            'total' => (string) $order->total,
            'status' => $order->status,
            'reference' => $order->reference,
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            session()->flash('success', 'تم حفظ البيانات بنجاح!');
            if ($request->boolean('print')) {
                session()->flash('print_order_id', $order->id);
            }

            return response()->json([
                'redirect' => route('sales.orders.index'),
                'ok' => true,
                'print_order_id' => $request->boolean('print') ? $order->id : null,
            ]);
        }

        $redirect = redirect()->route('sales.orders.index')->with('success', 'تم حفظ البيانات بنجاح!');
        if ($request->boolean('print')) {
            return $redirect->with('print_order_id', $order->id);
        }

        return $redirect;
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load([
            'customer',
            'items.item:id,code,name_ar,type',
            'deliveryOrders' => fn ($q) => $q->orderByDesc('id'),
            'attachments',
            'accountingJournalEntry',
        ]);

        return view('sales.orders.show', compact('salesOrder'));
    }

    public function storeAttachments(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status !== SalesOrder::STATUS_PENDING) {
            return back()->with('error', 'يمكن إضافة المرفقات لأوامر البيع في حالة «معلق» فقط.');
        }

        $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'attachments.required' => 'اختر ملفاً واحداً على الأقل.',
        ]);

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        $uid = (int) (auth()->id() ?? 1);
        DB::transaction(function () use ($salesOrder, $uploads, $uid): void {
            $this->persistMorphAttachments($salesOrder, $uploads, $uid, 'sales-orders');
        });

        return back()->with('success', 'تم حفظ المرفقات.');
    }

    public function completeAccounting(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        return back()->with(
            'error',
            'تم إيقاف الترحيل المحاسبي من أمر البيع. أنشئ فاتورة مبيعات مرتبطة بالأمر وارحّلها — القيود والمخزون تُسجَّل عند ترحيل الفاتورة فقط.'
        );
    }

    public function print(SalesOrder $salesOrder): View
    {
        $salesOrder->load([
            'customer',
            'items.item:id,code,name_ar,type',
        ]);

        return view('sales.orders.print', compact('salesOrder'));
    }

    public function index(Request $request): View|\Illuminate\Http\Response
    {
        $query = SalesOrder::with('customer')
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('reference', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        if ($request->get('export') === 'csv') {
            $rows = $query->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم الطلب,العميل,تاريخ الطلب,التسليم المتوقع,الإجمالي,الحالة\n";
            foreach ($rows as $o) {
                $csv .= '"SO-'.$o->id.'","'.str_replace('"', '""', $o->customer?->name ?? '').'","'.($o->order_date?->format('Y-m-d') ?? '').'","'.($o->expected_delivery?->format('Y-m-d') ?? '').'",'.(float) ($o->total ?? 0).',"'.($o->status ?? '')."\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="sales-orders-'.date('Y-m-d').'.csv"',
            ]);
        }

        $orders = $query->paginate(15)->withQueryString();

        $rows = $orders->getCollection()->map(function ($order) {
            $orderDate = $order->order_date?->format('Y-m-d');
            if ($orderDate === '1970-01-01') {
                $orderDate = '—';
            }

            $expectedDelivery = $order->expected_delivery?->format('Y-m-d');
            if ($expectedDelivery === '1970-01-01') {
                $expectedDelivery = '—';
            }

            return (object) [
                'id' => $order->id,
                'order_number' => 'SO-'.$order->id,
                'customer_name' => $order->customer?->name ?? '—',
                'order_date' => $orderDate ?? '—',
                'expected_delivery' => $expectedDelivery ?? '—',
                'total' => (float) $order->total,
                'status' => $order->status,
            ];
        });
        $orders->setCollection($rows);

        $allOrders = SalesOrder::all();
        $totalOrders = $allOrders->count();
        $pendingCount = $allOrders->where('status', 'معلق')->count();
        $confirmedValue = $allOrders->where('status', 'مكتمل')->sum('total');
        $cancelledValue = $allOrders->where('status', 'ملغي')->sum('total');

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $statuses = ['معلق', 'مكتمل', 'ملغي'];

        return view('sales.orders.index', [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'pendingCount' => $pendingCount,
            'confirmedValue' => $confirmedValue,
            'cancelledValue' => $cancelledValue,
            'customers' => $customers,
            'statuses' => $statuses,
        ]);
    }

    /**
     * عند عدم اختيار مخزن للبند: اختر مخزناً نشطاً يوجد فيه ربط للصنف،
     * مفضّلاً من يكفي الكمية ثم الأعلى توفراً. يفضّل ذلك على استخدام أعلى id فقط.
     */
    private function resolveWarehouseIdForOrderLine(int $itemId, float $quantityNeeded, ?int $fallbackWarehouseId): ?int
    {
        $activeWarehouseIds = Warehouse::active()->pluck('id');
        if ($activeWarehouseIds->isEmpty()) {
            return $fallbackWarehouseId;
        }

        $pivots = ItemWarehouse::query()
            ->where('item_id', $itemId)
            ->whereIn('warehouse_id', $activeWarehouseIds)
            ->get();

        if ($pivots->isEmpty()) {
            return $fallbackWarehouseId;
        }

        $sufficient = $pivots->first(function (ItemWarehouse $p) use ($quantityNeeded): bool {
            return $p->available_quantity + 0.0000001 >= $quantityNeeded;
        });
        if ($sufficient) {
            return (int) $sufficient->warehouse_id;
        }

        $best = $pivots->sortByDesc(fn (ItemWarehouse $p) => $p->available_quantity)->first();

        return $best ? (int) $best->warehouse_id : $fallbackWarehouseId;
    }
}
