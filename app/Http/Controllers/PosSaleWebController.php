<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\PosSession;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\PosAccountingService;
use App\Services\PosCostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class PosSaleWebController extends Controller
{
    public function index(Request $request): View
    {
        $uid = (int) auth()->id();
        $sales = PosSale::query()
            ->where('user_id', $uid)
            ->with(['posDevice'])
            ->completed()
            ->latest()
            ->paginate(25);

        return view('pos.sales.index', compact('sales'));
    }

    public function store(
        Request $request,
        InventoryService $inventory,
        PosCostingService $costing,
        PosAccountingService $accounting,
    ): RedirectResponse {
        $uid = (int) auth()->id();
        $validated = $request->validate([
            'pos_device_id' => ['required', 'exists:pos_devices,id'],
            'pos_session_id' => ['nullable', 'exists:pos_sessions,id'],
            'payment_method' => ['required', 'in:cash,card,bank,mixed,other'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
        ]);

        $device = PosDevice::query()
            ->where('user_id', $uid)
            ->findOrFail((int) $validated['pos_device_id']);

        if (! $device->warehouse_id) {
            return back()->withErrors([
                'pos_device_id' => 'جهاز نقطة البيع غير مرتبط بمستودع؛ لا يمكن إتمام البيع.',
            ])->withInput();
        }

        if (! Warehouse::query()->whereKey($device->warehouse_id)->exists()) {
            return back()->withErrors([
                'pos_device_id' => 'مستودع الجهاز غير صالح.',
            ])->withInput();
        }

        $session = null;
        if (! empty($validated['pos_session_id'])) {
            $session = PosSession::query()
                ->where('user_id', $uid)
                ->findOrFail((int) $validated['pos_session_id']);
            if ($session->pos_device_id !== $device->id || $session->status !== PosSession::STATUS_OPEN) {
                return back()->withErrors([
                    'pos_session_id' => 'الجلسة غير صالحة أو غير مفتوحة لهذا الجهاز.',
                ])->withInput();
            }
        }

        $qtyByItemId = [];
        foreach ($validated['lines'] as $line) {
            $iid = (int) $line['item_id'];
            $qtyByItemId[$iid] = ($qtyByItemId[$iid] ?? 0.0) + (float) $line['quantity'];
        }

        $unitCostByItemId = [];

        try {
            foreach ($qtyByItemId as $itemId => $needQty) {
                $item = Item::query()->findOrFail($itemId);
                if ((int) $item->user_id !== $uid) {
                    return back()->withErrors([
                        'stock' => 'تعذّر إتمام العملية: يوجد صنف غير تابع لحسابك.',
                    ])->withInput();
                }
                $unitCostByItemId[$itemId] = $costing->unitCostForFinishedGoodSale($item);

                $pivot = ItemWarehouse::query()
                    ->where('item_id', $itemId)
                    ->where('warehouse_id', $device->warehouse_id)
                    ->where('user_id', (int) $item->user_id)
                    ->first();

                $available = $pivot ? (float) $pivot->available_quantity : 0.0;

                if ($available + 0.0000001 < $needQty) {
                    $suffix = $available <= 0.0000001
                        ? 'الرصيد المتاح صفر في مستودع الجهاز.'
                        : sprintf('متاح: %s، مطلوب إجمالاً: %s.', $this->fmtQty($available), $this->fmtQty($needQty));

                    return back()->withErrors([
                        'stock' => 'الصنف «'.$item->code.'»: '.$suffix,
                    ])->withInput();
                }
            }
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['lines' => $e->getMessage()])->withInput();
        }

        $total = 0.0;
        foreach ($validated['lines'] as $line) {
            $total += round((float) $line['quantity'] * (float) $line['unit_price'], 4);
        }

        try {
            $sale = DB::transaction(function () use ($validated, $device, $session, $inventory, $accounting, $unitCostByItemId, $total) {
                /** @var PosSale $sale */
                $sale = PosSale::create([
                    'user_id' => $uid,
                    'pos_device_id' => $device->id,
                    'pos_session_id' => $session?->id,
                    'receipt_number' => PosSale::nextReceiptNumber($uid),
                    'total_price' => round($total, 4),
                    'payment_method' => $validated['payment_method'],
                    'status' => PosSale::STATUS_COMPLETED,
                ]);

                foreach ($validated['lines'] as $line) {
                    $item = Item::query()->findOrFail($line['item_id']);
                    $qty = (float) $line['quantity'];
                    $unit = (float) $line['unit_price'];
                    $lineTotal = round($qty * $unit, 4);
                    $unitCost = $unitCostByItemId[(int) $item->id];

                    PosSaleLine::create([
                        'pos_sale_id' => $sale->id,
                        'item_id' => $item->id,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'line_total' => $lineTotal,
                        'unit_cost' => $unitCost,
                    ]);

                    $inventory->stockOutForPosSale($item, (int) $device->warehouse_id, $qty, $sale);
                }

                $sale->refresh()->load('lines');

                $accounting->recordJournalForPosSale($sale);

                AuditLog::logModuleEvent('pos_sale_completed', [
                    'receipt_number' => $sale->receipt_number,
                    'total_price' => (string) $sale->total_price,
                    'payment_method' => $sale->payment_method,
                    'pos_device_id' => $device->id,
                    'warehouse_id' => $device->warehouse_id,
                    'journal_entry_id' => $sale->journal_entry_id,
                ], $sale->fresh());

                return $sale->load(['lines', 'posDevice']);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'sale' => $e->getMessage(),
            ])->withInput();
        }

        return redirect()
            ->route('pos.sales.show', $sale)
            ->with('success', 'تم تسجيل عملية البيع وصرف المخزون والترحيل المحاسبي.');
    }

    private function fmtQty(float $q): string
    {
        return rtrim(rtrim(number_format($q, 4, '.', ''), '0'), '.') ?: '0';
    }

    public function show(PosSale $posSale): View
    {
        abort_if((int) $posSale->user_id !== (int) auth()->id(), 403);

        $posSale->load(['lines.item', 'posDevice.warehouse', 'posSession', 'journalEntry']);

        return view('pos.sales.show', compact('posSale'));
    }
}
