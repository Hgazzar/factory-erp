<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\CompanySetting;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchasing\PurchaseReturnPostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class PurchaseReturnWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly PurchaseReturnPostingService $postingService,
    ) {}

    public function index(Request $request): View|Response
    {
        $query = PurchaseReturn::with(['supplier', 'purchaseInvoice', 'warehouse'])
            ->withCount('items')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qry) use ($q) {
                $qry->where('code', 'like', "%{$q}%")
                    ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"));
            });
        }

        if ($request->get('export') === 'csv') {
            $rows = (clone $query)->limit(5000)->get();
            $csv = "\xEF\xBB\xBF";
            $csv .= "رقم المرتجع,المورد,الفاتورة,التاريخ,السبب,عدد الأصناف,الإجمالي,الحالة\n";
            foreach ($rows as $r) {
                $invRef = $r->purchaseInvoice?->reference ?: ($r->purchase_invoice_id ? 'PINV-'.$r->purchase_invoice_id : '');
                $csv .= '"'.str_replace('"', '""', $r->code ?? '').'","'
                    .str_replace('"', '""', $r->supplier?->getLocalizedDisplayName() ?? '').'","'
                    .str_replace('"', '""', $invRef).'","'
                    .($r->date?->format('Y-m-d') ?? '').'","'
                    .str_replace('"', '""', $r->reason_type ?? $r->reason ?? '').'",'
                    .($r->items_count ?? 0).','.(float) $r->total.',"'.($r->status_label ?? '')."\"\n";
            }

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="purchase-returns-'.date('Y-m-d').'.csv"',
            ]);
        }

        $returns = $query->paginate(20)->withQueryString();

        $totalReturnedAmount = (float) PurchaseReturn::where('status', PurchaseReturn::STATUS_COMPLETED)->sum('total');
        $totalCount = PurchaseReturn::count();
        $completedCount = PurchaseReturn::where('status', PurchaseReturn::STATUS_COMPLETED)->count();
        $pendingCount = PurchaseReturn::where('status', PurchaseReturn::STATUS_PENDING)->count();

        $reasonTypes = ['تالف', 'خطأ في الشحن', 'عدم المطابقة', 'آخر'];

        return view('purchases.returns.index', compact(
            'returns',
            'totalReturnedAmount',
            'totalCount',
            'completedCount',
            'pendingCount',
            'reasonTypes'
        ));
    }

    public function create(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name_ar')->get();
        $items = Item::where('is_active', true)->orderBy('code')->get();
        $returnTypes = ['معيب', 'غير مطابق', 'تالف', 'خطأ في الشحن', 'آخر'];
        $lineStatuses = ['معيب', 'سليم', 'غير مطابق', 'تالف', 'أخرى'];
        $defaultVatPercent = CompanySetting::resolvedDefaultVatPercent($tenantUserId);

        return view('purchases.returns.create', compact(
            'suppliers',
            'warehouses',
            'items',
            'returnTypes',
            'lineStatuses',
            'defaultVatPercent',
        ));
    }

    public function invoicesBySupplier(Request $request): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $supplierId = $request->integer('supplier_id');
        if ($supplierId <= 0) {
            return response()->json(['invoices' => []]);
        }

        $invoices = PurchaseInvoice::query()
            ->where('supplier_id', $supplierId)
            ->whereNotNull('posted_at')
            ->whereNotNull('journal_entry_id')
            ->orderByDesc('date')
            ->get(['id', 'date', 'reference', 'total', 'paid_amount'])
            ->map(fn (PurchaseInvoice $inv) => [
                'id' => $inv->id,
                'label' => ($inv->reference ?: 'PINV-'.$inv->id).' ('.$inv->date?->format('Y-m-d').') — SAR '.number_format((float) $inv->total, 2),
                'date' => $inv->date?->format('Y-m-d'),
                'total' => (float) $inv->total,
                'balance' => max(0, (float) $inv->total - (float) ($inv->paid_amount ?? 0)),
            ]);

        return response()->json(['invoices' => $invoices]);
    }

    public function invoiceItems(PurchaseInvoice $invoice): JsonResponse
    {
        $invoice->load(['items.item']);

        if (! $invoice->isPosted()) {
            return response()->json(['items' => [], 'warehouse_id' => $invoice->warehouse_id], 422);
        }

        $items = $invoice->items->map(function ($line) use ($invoice) {
            $meta = $this->postingService->maxReturnableForInvoiceLine($invoice, (int) $line->item_id);

            return [
                'purchase_invoice_item_id' => (int) $line->id,
                'item_id' => $line->item_id,
                'item_name' => $line->item?->name_ar ?? $line->item?->name_en ?? $line->item?->code ?? '-',
                'invoice_quantity' => (float) $line->quantity,
                'returned_quantity' => (float) $line->quantity - $meta['available'],
                'max_returnable' => $meta['available'],
                'unit_price' => (float) $line->unit_price,
                'discount' => (float) ($line->discount ?? 0),
                'vat_percent' => (float) ($line->vat_percent ?? $invoice->vat_rate ?? 0),
                'unit_cost' => (float) ($line->weighted_unit_cost ?? $line->unit_price ?? 0),
            ];
        })->filter(fn ($r) => $r['max_returnable'] > 0)->values();

        return response()->json([
            'items' => $items,
            'warehouse_id' => $invoice->warehouse_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('user_id', $tenantUserId)],
            'purchase_invoice_id' => ['nullable', Rule::exists('purchase_invoices', 'id')->where('user_id', $tenantUserId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('user_id', $tenantUserId)],
            'date' => ['required', 'date'],
            'reason_type' => ['required', 'string', 'max:100'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'max:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $tenantUserId)],
            'lines.*.purchase_invoice_item_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.vat_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.line_status' => ['nullable', 'string', 'max:50'],
        ], [
            'supplier_id.required' => 'المورد مطلوب.',
            'warehouse_id.required' => 'المستودع مطلوب.',
            'date.required' => 'التاريخ مطلوب.',
            'lines.required' => 'يجب إضافة بند واحد على الأقل.',
        ]);

        try {
            $this->postingService->createAndPost($tenantUserId, [
                'supplier_id' => (int) $data['supplier_id'],
                'purchase_invoice_id' => ! empty($data['purchase_invoice_id']) ? (int) $data['purchase_invoice_id'] : null,
                'warehouse_id' => (int) $data['warehouse_id'],
                'date' => $data['date'],
                'reason_type' => $data['reason_type'],
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? 'SAR',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ], $data['lines']);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('purchases.returns.index')
            ->with('success', 'تم ترحيل مرتجع المشتريات: خصم المخزون، القيد العكسي، وتحديث الفاتورة الأصلية.');
    }
}
