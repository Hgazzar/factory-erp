<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    /** أنواع الحركات وعناوينها وأيقوناتها */
    public const MOVEMENT_TYPES = [
        'transfer_in'     => ['label' => 'تحويل وارد', 'icon' => 'in'],
        'transfer_out'    => ['label' => 'تحويل صادر', 'icon' => 'out'],
        'adjustment_in'   => ['label' => 'تسوية إضافة', 'icon' => 'in'],
        'adjustment_out'  => ['label' => 'تسوية خصم', 'icon' => 'out'],
        'inventory_audit' => ['label' => 'جرد', 'icon' => 'audit'],
    ];

    /**
     * @return View|Response
     */
    public function index(Request $request)
    {
        $query = StockMovement::with(['warehouse', 'item', 'reference'])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->get();

        $balances = [];
        foreach ($movements as $m) {
            $key = $m->warehouse_id . '_' . $m->item_id;
            if (!isset($balances[$key])) {
                $balances[$key] = 0;
            }
            $balances[$key] += (float) $m->quantity;
            $m->balance_after = $balances[$key];
        }

        $warehouses = Warehouse::where('is_active', true)->orderBy('name_ar')->get(['id', 'code', 'name_ar']);
        $items = Item::orderBy('name_ar')->get(['id', 'code', 'name_ar']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->view('inventory.movements.partials.tbody', [
                'movements' => $movements,
                'types' => self::MOVEMENT_TYPES,
            ]);
        }

        return view('inventory.movements.index', [
            'movements' => $movements,
            'warehouses' => $warehouses,
            'items' => $items,
            'types' => self::MOVEMENT_TYPES,
        ]);
    }
}
