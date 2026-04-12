<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductionRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionReportWebController extends Controller
{
    public function index(Request $request): View
    {
        $items = Item::orderBy('code')->get();

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $itemId = $request->input('item_id');

        $query = ProductionRecord::with(['item', 'employee.user'])
            ->orderBy('recorded_at', 'desc');

        if ($fromDate) {
            $query->whereDate('recorded_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('recorded_at', '<=', $toDate);
        }
        if ($itemId) {
            $query->where('item_id', $itemId);
        }

        $records = $query->paginate(30);

        return view('reports.production.index', compact(
            'items',
            'records',
            'fromDate',
            'toDate',
            'itemId'
        ));
    }

    /**
     * تفاصيل سجل إنتاج واحد (للمودال).
     */
    public function show(ProductionRecord $record): JsonResponse
    {
        $record->load(['item.unit', 'employee.user', 'productionShift.shift', 'productionShift.productionLine', 'journalEntry.items.account']);
        $je = $record->journalEntry;
        $journalItems = $je ? $je->items->map(fn ($i) => [
            'account' => $i->account?->code . ' - ' . $i->account?->name_ar,
            'description' => $i->description,
            'debit' => (float) $i->debit,
            'credit' => (float) $i->credit,
        ]) : [];
        return response()->json([
            'id' => $record->id,
            'recorded_at' => $record->recorded_at?->format('Y-m-d H:i'),
            'item_code' => $record->item?->code,
            'item_name' => $record->item?->name_ar,
            'quantity' => (float) $record->quantity,
            'scrap_quantity' => (float) $record->scrap_quantity,
            'scrap_reason' => $record->scrap_reason,
            'downtime_reason' => $record->downtime_reason,
            'downtime_lost_hours' => $record->downtime_lost_hours,
            'employee' => $record->employee?->name,
            'notes' => $record->notes,
            'journal_reference' => $je?->reference,
            'journal_date' => $je?->date?->format('Y-m-d'),
            'journal_total' => $je ? (float) $je->total : null,
            'journal_items' => $journalItems,
        ]);
    }
}

