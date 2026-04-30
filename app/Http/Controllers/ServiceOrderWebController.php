<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\InstalledAsset;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\ServiceOrder;
use App\Models\ServicePart;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ServiceOrderWebController extends Controller
{
    public function index(Request $request): View
    {
        $q = ServiceOrder::query()->with(['customer', 'assignedTechnician', 'deliveryOrder']);

        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('priority')) {
            $q->where('priority', $request->string('priority'));
        }

        $orders = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('services.orders.index', compact('orders'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $salesOrderId = $request->integer('sales_order_id') ?: null;
        $deliveryOrderId = $request->integer('delivery_order_id') ?: null;

        $salesOrder = $salesOrderId ? SalesOrder::with('customer')->find($salesOrderId) : null;
        $deliveryOrder = $deliveryOrderId
            ? DeliveryOrder::with(['salesOrder.customer', 'items.item'])->find($deliveryOrderId)
            : null;

        if ($deliveryOrder && ! $salesOrder) {
            $salesOrder = $deliveryOrder->salesOrder;
        }

        $installedAssets = collect();
        if ($deliveryOrder) {
            $installedAssets = InstalledAsset::query()
                ->where('delivery_order_id', $deliveryOrder->id)
                ->with('item')
                ->orderBy('id')
                ->get();
        }

        $technicians = User::query()
            ->where(function ($query) {
                $query->where('is_technician', true)
                    ->orWhereIn('role', ['admin', 'super_admin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $warehouses = Warehouse::query()->orderBy('name_ar')->get();
        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get();

        return view('services.orders.create', compact(
            'salesOrder',
            'deliveryOrder',
            'installedAssets',
            'technicians',
            'warehouses',
            'customers',
            'salesOrderId',
            'deliveryOrderId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_type' => ['required', 'in:install,maintenance,repair'],
            'priority' => ['required', 'in:normal,urgent'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'delivery_order_id' => ['nullable', 'exists:delivery_orders,id'],
            'installed_asset_id' => ['nullable', 'exists:installed_assets,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'assigned_technician_id' => ['nullable', 'exists:users,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'labor_amount' => ['nullable', 'numeric', 'min:0'],
            'is_paid_service' => ['sometimes', 'boolean'],
        ]);

        if ($data['service_type'] === ServiceOrder::TYPE_INSTALL) {
            $data['is_paid_service'] = $request->boolean('is_paid_service', true);
            $data['outside_warranty'] = false;
        } else {
            ServiceOrder::applyWarrantyRules($data);
        }

        if (! empty($data['delivery_order_id'])) {
            $d = DeliveryOrder::query()->find($data['delivery_order_id']);
            if ($d && empty($data['customer_id']) && $d->salesOrder) {
                $data['customer_id'] = $d->salesOrder->customer_id;
            }
        }
        if (! empty($data['sales_order_id']) && empty($data['customer_id'])) {
            $so = SalesOrder::query()->find($data['sales_order_id']);
            if ($so) {
                $data['customer_id'] = $so->customer_id;
            }
        }

        $data['reference_number'] = ServiceOrder::generateReferenceNumber();
        $data['status'] = ! empty($data['assigned_technician_id'])
            ? ServiceOrder::STATUS_ASSIGNED
            : ServiceOrder::STATUS_OPEN;
        $data['created_by'] = $request->user()->id;

        $order = ServiceOrder::query()->create($data);

        AuditTrail::log('create', 'service_orders', $order->id, null, [
            'reference_number' => $order->reference_number,
            'service_type' => $order->service_type,
            'status' => $order->status,
            'is_paid_service' => $order->is_paid_service,
            'outside_warranty' => $order->outside_warranty,
        ]);

        $redirect = redirect()->route('services.orders.show', $order)->with('success', 'تم إنشاء طلب الخدمة.');

        if ($order->outside_warranty) {
            $redirect->with('warning', 'تنبيه: الصيانة خارج فترة الضمان — الخدمة تُعتبر مدفوعة.');
        }

        return $redirect;
    }

    public function show(ServiceOrder $order): View
    {
        $order->load([
            'customer',
            'salesOrder',
            'deliveryOrder',
            'installedAsset.item',
            'assignedTechnician',
            'warehouse',
            'parts.item',
            'salesInvoice',
            'creator',
        ]);

        $technicians = User::query()
            ->where(function ($query) {
                $query->where('is_technician', true)
                    ->orWhereIn('role', ['admin', 'super_admin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $stockableItems = Item::query()
            ->whereIn('type', [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD])
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar', 'cost']);

        return view('services.orders.show', compact('order', 'technicians', 'stockableItems'));
    }

    public function assign(Request $request, ServiceOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'assigned_technician_id' => ['required', 'exists:users,id'],
        ]);

        $old = [
            'assigned_technician_id' => $order->assigned_technician_id,
            'status' => $order->status,
        ];

        $order->assigned_technician_id = $data['assigned_technician_id'];
        if (in_array($order->status, [ServiceOrder::STATUS_OPEN, ServiceOrder::STATUS_ASSIGNED], true)) {
            $order->status = ServiceOrder::STATUS_ASSIGNED;
        }
        $order->save();

        AuditTrail::log('update', 'service_orders', $order->id, $old, [
            'assigned_technician_id' => $order->assigned_technician_id,
            'status' => $order->status,
        ]);

        return back()->with('success', 'تم تعيين الفني.');
    }

    public function addPart(Request $request, ServiceOrder $order, InventoryService $inventory): RedirectResponse
    {
        if (in_array($order->status, [ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_CANCELLED], true)) {
            return back()->with('error', 'لا يمكن إضافة قطع لطلب مغلق.');
        }

        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $item = Item::query()->findOrFail($data['item_id']);
        $warehouseId = (int) $order->warehouse_id;

        try {
            DB::transaction(function () use ($order, $item, $data, $warehouseId, $inventory) {
                $part = ServicePart::query()->create([
                    'service_order_id' => $order->id,
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $data['quantity'],
                    'unit_cost' => (float) ($item->cost ?? 0),
                ]);

                $inventory->issueForService($item, $warehouseId, (float) $data['quantity'], $part);
            });
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditTrail::log('update', 'service_orders', $order->id, null, [
            'parts_added' => true,
            'item_id' => $item->id,
            'quantity' => (float) $data['quantity'],
        ]);

        return back()->with('success', 'تم صرف القطعة من المخزون وتسجيلها على الطلب.');
    }

    public function complete(Request $request, ServiceOrder $order): RedirectResponse
    {
        if ($order->status === ServiceOrder::STATUS_COMPLETED) {
            return back()->with('error', 'الطلب مكتمل مسبقاً.');
        }

        $request->validate([
            'labor_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $old = ['status' => $order->status];

        DB::transaction(function () use ($order, $request) {
            if ($request->filled('labor_amount')) {
                $order->labor_amount = $request->input('labor_amount');
            }
            $order->status = ServiceOrder::STATUS_COMPLETED;
            $order->executed_at = now()->toDateString();
            $order->save();

            if ($order->is_paid_service) {
                $order->createDraftInvoiceIfNeeded();
            }
        });

        $order->refresh();

        AuditTrail::log('update', 'service_orders', $order->id, $old, [
            'status' => $order->status,
            'executed_at' => $order->executed_at?->toDateString(),
            'sales_invoice_id' => $order->sales_invoice_id,
        ]);

        $msg = 'تم إغلاق طلب الخدمة.';
        if ($order->sales_invoice_id) {
            $msg .= ' وتم إنشاء مسودة فاتورة مرتبطة.';
        }

        return back()->with('success', $msg);
    }

    public function cancel(ServiceOrder $order): RedirectResponse
    {
        if ($order->status === ServiceOrder::STATUS_COMPLETED) {
            return back()->with('error', 'لا يمكن إلغاء طلب مكتمل.');
        }

        $old = ['status' => $order->status];
        $order->status = ServiceOrder::STATUS_CANCELLED;
        $order->save();

        AuditTrail::log('update', 'service_orders', $order->id, $old, ['status' => $order->status]);

        return back()->with('success', 'تم إلغاء الطلب.');
    }
}
