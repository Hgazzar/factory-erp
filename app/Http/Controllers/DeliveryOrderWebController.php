<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeliveryOrderWebController extends Controller
{
    public function index(): View
    {
        $deliveryOrders = DeliveryOrder::query()
            ->with(['salesOrder.customer'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('sales.delivery_orders.index', compact('deliveryOrders'));
    }

    public function create(SalesOrder $salesOrder): View|RedirectResponse
    {
        if ($salesOrder->status === 'ملغي') {
            return redirect()
                ->route('sales.orders.show', $salesOrder->id)
                ->with('error', 'لا يمكن إنشاء أمر توريد لأمر بيع ملغى.');
        }

        $salesOrder->load(['items.item:id,code,name_ar,type', 'customer']);

        $lines = $salesOrder->items->map(function ($line) {
            $remaining = $line->remainingQuantityForDelivery();

            return [
                'sales_order_item_id' => $line->id,
                'item_id' => $line->item_id,
                'code' => $line->item?->code ?? '—',
                'name_ar' => $line->item?->name_ar ?? '—',
                'type' => $line->item?->type ?? '',
                'ordered' => (float) $line->quantity,
                'remaining' => $remaining,
            ];
        })->values()->all();

        if (collect($lines)->every(fn ($l) => $l['remaining'] <= 0)) {
            return redirect()
                ->route('sales.orders.show', $salesOrder->id)
                ->with('error', 'لا توجد كميات متبقية للتوريد على هذا الأمر.');
        }

        $warehouseOptions = Warehouse::query()
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar'])
            ->map(fn (Warehouse $w) => [
                'value' => (string) $w->id,
                'label' => $w->code.' — '.$w->name_ar,
            ])
            ->values()
            ->all();

        return view('sales.delivery_orders.create', [
            'salesOrder' => $salesOrder,
            'lines' => $lines,
            'warehouseOptions' => $warehouseOptions,
        ]);
    }

    public function store(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status === 'ملغي') {
            return redirect()
                ->route('sales.orders.show', $salesOrder->id)
                ->with('error', 'لا يمكن إنشاء أمر توريد لأمر بيع ملغى.');
        }

        $salesOrder->load('items');

        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_item_id' => ['required', 'integer', 'exists:sales_order_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $itemIdsBySoLine = $salesOrder->items->keyBy('id');

        $payload = [];
        foreach ($validated['lines'] as $row) {
            $soItemId = (int) $row['sales_order_item_id'];
            $qty = (float) $row['quantity'];

            if ($qty <= 0) {
                continue;
            }

            $soLine = $itemIdsBySoLine->get($soItemId);
            if (! $soLine || (int) $soLine->sales_order_id !== (int) $salesOrder->id) {
                return back()->withInput()->with('error', 'بند غير صالح أو لا يتبع أمر البيع المحدد.');
            }

            $remaining = $soLine->remainingQuantityForDelivery();
            if ($qty - $remaining > 0.0001) {
                return back()->withInput()->with(
                    'error',
                    sprintf('الكمية المطلوبة للبند %s تتجاوز المتبقي المسموح (%s).', $soLine->item?->code ?? (string) $soItemId, rtrim(rtrim(number_format($remaining, 4, '.', ''), '0'), '.'))
                );
            }

            $payload[] = [
                'sales_order_item_id' => $soItemId,
                'item_id' => (int) $soLine->item_id,
                'quantity' => $qty,
            ];
        }

        if ($payload === []) {
            return back()->withInput()->with('error', 'أدخل كمية أكبر من صفر لسطر واحد على الأقل.');
        }

        $createdDelivery = null;

        try {
            DB::transaction(function () use ($salesOrder, $validated, $payload, &$createdDelivery) {
                $delivery = DeliveryOrder::create([
                    'user_id' => (int) $salesOrder->user_id,
                    'sales_order_id' => $salesOrder->id,
                    'warehouse_id' => (int) $validated['warehouse_id'],
                    'delivery_number' => 'TMP-'.Str::uuid()->toString(),
                    'status' => DeliveryOrder::STATUS_PENDING,
                    'delivery_date' => $validated['delivery_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $delivery->delivery_number = sprintf('DO-%s-%06d', now()->format('Y'), $delivery->id);
                $delivery->save();

                foreach ($payload as $row) {
                    DeliveryOrderItem::create([
                        'delivery_order_id' => $delivery->id,
                        'sales_order_item_id' => $row['sales_order_item_id'],
                        'item_id' => $row['item_id'],
                        'quantity' => $row['quantity'],
                    ]);
                }

                $createdDelivery = $delivery;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.delivery-orders.show', $createdDelivery->id)
            ->with('success', 'تم إنشاء أمر التوريد بنجاح. يمكنك تأكيد التسليم لخصم المخزون عند التسليم الفعلي.');
    }

    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load([
            'salesOrder.customer',
            'warehouse:id,code,name_ar',
            'items.salesOrderItem',
            'items.item:id,code,name_ar,type,current_stock',
        ]);

        return view('sales.delivery_orders.show', compact('deliveryOrder'));
    }

    public function deliver(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        try {
            $deliveryOrder->markAsDelivered();
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.delivery-orders.show', $deliveryOrder->id)
            ->with('success', 'تم تأكيد التسليم. خصم المخزون يتم عند ترحيل فاتورة المبيعات المرتبطة.');
    }
}
