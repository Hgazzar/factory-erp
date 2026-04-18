<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\UniversalImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseOrderWebController extends Controller
{
    use PersistsMorphAttachments;

    private const PENDING_STATUS = 'معلق';

    public function importTemplate(): Response
    {
        $csv = "\xEF\xBB\xBF";
        $csv .= implode(',', [
            'Order Number',
            'Supplier Code',
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
            'Content-Disposition' => 'attachment; filename="purchase-orders-import-template.csv"',
        ]);
    }

    public function import(Request $request, UniversalImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        try {
            $summary = $importService->import($request->file('file'), UniversalImportService::ENTITY_PURCHASE_ORDERS);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.orders.index')
            ->with('success', "تم استيراد أوامر الشراء. نجاح: {$summary['created']} إضافة، {$summary['updated']} تحديث. فشل: {$summary['failed']}. عدد الصفوف المعالجة: ".($summary['total_rows_processed'] ?? 0).' | عدد الرؤوس الناجحة: '.($summary['successful_headers'] ?? 0))
            ->with('import_result', $summary);
    }

    public function index(Request $request): View
    {
        $orders = PurchaseOrder::with('supplier')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('purchases.orders.index', ['orders' => $orders]);
    }

    public function show(PurchaseOrder $order): View
    {
        $order->load(['supplier', 'items.item', 'attachments']);

        return view('purchases.orders.show', ['order' => $order]);
    }

    public function create(): View
    {
        $suppliers = Supplier::query()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar'])
            ->map(fn (Supplier $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->getLocalizedDisplayName(),
            ])
            ->values()
            ->all();
        $items = Item::active()->orderBy('code')->get([
            'id', 'code', 'name_ar', 'cost', 'min_stock', 'max_stock',
        ])->map(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'name_ar' => $item->name_ar,
                'cost' => (float) $item->cost,
                'total_stock' => (float) $item->total_quantity,
                'max_stock' => $item->max_stock !== null ? (float) $item->max_stock : null,
            ];
        });

        $prefillLines = session()->pull('purchase_order_prefill_lines', []);
        $prefillReference = session()->pull('purchase_order_prefill_reference');

        $nextOrderNumber = PurchaseOrder::generateNextOrderNumberForUser((int) (auth()->id() ?? 1));

        return view('purchases.orders.create', [
            'suppliers' => $suppliers,
            'items' => $items,
            'prefillLines' => is_array($prefillLines) ? $prefillLines : [],
            'prefillReference' => $prefillReference,
            'nextOrderNumber' => $nextOrderNumber,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', auth()->id())],
            'order_date' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:5'],
            'reference' => ['nullable', 'string', 'max:100'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,csv'],
        ]);

        $currency = $data['currency'] ?? 'SAR';
        $shippingCost = (float) ($data['shipping_cost'] ?? 0);

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
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => $discount,
                'tax_percent' => $tax,
                'line_total' => round($lineTotal, 4),
                'description' => $line['description'] ?? null,
            ];
        })->filter(fn ($l) => $l['quantity'] > 0)->values();

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.');
        }

        $subtotal = $lines->sum(fn ($l) => (float) $l['quantity'] * (float) $l['unit_price']);
        $totalDiscount = $lines->sum(fn ($l) => (float) $l['quantity'] * (float) $l['unit_price'] * (float) $l['discount_percent'] / 100);
        $totalTax = $lines->sum(fn ($l) => ((float) $l['quantity'] * (float) $l['unit_price'] * (1 - (float) $l['discount_percent'] / 100)) * (float) $l['tax_percent'] / 100);
        $total = $subtotal - $totalDiscount + $totalTax + $shippingCost;

        $uid = (int) (auth()->id() ?? 1);
        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        DB::transaction(function () use ($data, $lines, $subtotal, $totalDiscount, $totalTax, $total, $shippingCost, $currency, $uid, $uploads): void {
            $order = PurchaseOrder::create([
                'user_id' => $uid,
                'order_number' => PurchaseOrder::generateNextOrderNumberForUser($uid),
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'currency' => $currency,
                'reference' => $data['reference'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'shipping_cost' => $shippingCost,
                'internal_notes' => $data['internal_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'status' => 'معلق',
                'subtotal' => round($subtotal, 4),
                'total_discount' => round($totalDiscount, 4),
                'total_tax' => round($totalTax, 4),
                'total' => round($total, 4),
            ]);

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $this->persistMorphAttachments($order, $uploads, $uid, 'purchase-orders');
        });

        return redirect()->route('purchases.orders.index')->with('success', 'تم إنشاء أمر الشراء بنجاح.');
    }

    public function edit(PurchaseOrder $order): View|RedirectResponse
    {
        if ($order->status !== self::PENDING_STATUS) {
            return redirect()
                ->route('purchases.orders.show', $order)
                ->with('error', 'يمكن تعديل أوامر الشراء في حالة «معلق» فقط.');
        }

        $suppliers = Supplier::query()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'name_ar'])
            ->map(fn (Supplier $s) => [
                'id' => $s->id,
                'code' => $s->code,
                'name' => $s->getLocalizedDisplayName(),
            ])
            ->values()
            ->all();

        $items = Item::active()->orderBy('code')->get([
            'id', 'code', 'name_ar', 'cost', 'min_stock', 'max_stock',
        ])->map(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'name_ar' => $item->name_ar,
                'cost' => (float) $item->cost,
                'total_stock' => (float) $item->total_quantity,
                'max_stock' => $item->max_stock !== null ? (float) $item->max_stock : null,
            ];
        });

        $order->load(['items', 'attachments']);

        $editOrderPayload = [
            'supplier_id' => $order->supplier_id,
            'order_date' => $order->order_date?->format('Y-m-d'),
            'expected_delivery_date' => $order->expected_delivery_date?->format('Y-m-d'),
            'shipping_cost' => (float) $order->shipping_cost,
            'lines' => $order->items->map(fn ($line) => [
                'item_id' => $line->item_id,
                'description' => $line->description ?? '',
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'discount_percent' => (float) $line->discount_percent,
                'tax_percent' => (float) $line->tax_percent,
            ])->all(),
        ];

        if (is_array($oldLines = old('lines'))) {
            $editOrderPayload['lines'] = collect($oldLines)
                ->map(fn (array $line) => [
                    'item_id' => (int) ($line['item_id'] ?? 0),
                    'description' => (string) ($line['description'] ?? ''),
                    'quantity' => (float) ($line['quantity'] ?? 0),
                    'unit_price' => (float) ($line['unit_price'] ?? 0),
                    'discount_percent' => (float) ($line['discount_percent'] ?? 0),
                    'tax_percent' => (float) ($line['tax_percent'] ?? 0),
                ])
                ->filter(fn (array $l) => $l['item_id'] > 0)
                ->values()
                ->all();
        }
        if (old('supplier_id')) {
            $editOrderPayload['supplier_id'] = (int) old('supplier_id');
        }
        foreach (['order_date' => 'order_date', 'expected_delivery_date' => 'expected_delivery_date'] as $key => $payloadKey) {
            if (old($key)) {
                $editOrderPayload[$payloadKey] = old($key);
            }
        }
        if (old('shipping_cost') !== null) {
            $editOrderPayload['shipping_cost'] = (float) old('shipping_cost');
        }

        return view('purchases.orders.create', [
            'suppliers' => $suppliers,
            'items' => $items,
            'prefillLines' => [],
            'prefillReference' => null,
            'nextOrderNumber' => $order->order_number,
            'editOrderModel' => $order,
            'editOrderPayload' => $editOrderPayload,
        ]);
    }

    public function update(Request $request, PurchaseOrder $order): RedirectResponse
    {
        if ($order->status !== self::PENDING_STATUS) {
            return redirect()
                ->route('purchases.orders.show', $order)
                ->with('error', 'يمكن تعديل أوامر الشراء في حالة «معلق» فقط.');
        }

        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', auth()->id())],
            'order_date' => ['required', 'date'],
            'currency' => ['nullable', 'string', 'max:5'],
            'reference' => ['nullable', 'string', 'max:100'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,csv'],
        ]);

        $currency = $data['currency'] ?? 'SAR';
        $shippingCost = (float) ($data['shipping_cost'] ?? 0);

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
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => $discount,
                'tax_percent' => $tax,
                'line_total' => round($lineTotal, 4),
                'description' => $line['description'] ?? null,
            ];
        })->filter(fn ($l) => $l['quantity'] > 0)->values();

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', 'يجب إضافة على الأقل بنداً واحداً بقيمة صحيحة.');
        }

        $subtotal = $lines->sum(fn ($l) => (float) $l['quantity'] * (float) $l['unit_price']);
        $totalDiscount = $lines->sum(fn ($l) => (float) $l['quantity'] * (float) $l['unit_price'] * (float) $l['discount_percent'] / 100);
        $totalTax = $lines->sum(fn ($l) => ((float) $l['quantity'] * (float) $l['unit_price'] * (1 - (float) $l['discount_percent'] / 100)) * (float) $l['tax_percent'] / 100);
        $total = $subtotal - $totalDiscount + $totalTax + $shippingCost;

        $uid = (int) (auth()->id() ?? 1);
        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        DB::transaction(function () use ($order, $data, $currency, $shippingCost, $lines, $subtotal, $totalDiscount, $totalTax, $total, $uploads, $uid): void {
            $order->update([
                'supplier_id' => $data['supplier_id'],
                'order_date' => $data['order_date'],
                'currency' => $currency,
                'reference' => $data['reference'] ?? null,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'shipping_cost' => $shippingCost,
                'internal_notes' => $data['internal_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'subtotal' => round($subtotal, 4),
                'total_discount' => round($totalDiscount, 4),
                'total_tax' => round($totalTax, 4),
                'total' => round($total, 4),
            ]);

            $order->items()->delete();

            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $this->persistMorphAttachments($order, $uploads, $uid, 'purchase-orders');
        });

        return redirect()->route('purchases.orders.show', $order)->with('success', 'تم تحديث أمر الشراء.');
    }

    public function destroy(PurchaseOrder $order): RedirectResponse
    {
        if ($order->status !== self::PENDING_STATUS) {
            return redirect()
                ->route('purchases.orders.index')
                ->with('error', 'يمكن حذف أوامر الشراء في حالة «معلق» فقط.');
        }

        DB::transaction(function () use ($order): void {
            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('purchases.orders.index')->with('success', 'تم حذف أمر الشراء.');
    }
}
