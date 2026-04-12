<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ProductionRecord;
use App\Models\ProductionLog;
use App\Models\ProductionShift;
use Illuminate\Support\Facades\DB;
use App\Support\DefaultLedgerAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductionEntryWebController extends Controller
{
    public function create(Request $request): View
    {
        $date = $request->date('date', now()->toDateString());

        $productionShifts = ProductionShift::with(['shift', 'productionLine', 'machine'])
            ->whereDate('date', $date)
            ->orderBy('shift_id')
            ->get();

        $items = Item::active()->orderBy('code')->get();

        $recentLogs = ProductionLog::with(['productionShift.shift', 'item'])
            ->whereHas('productionShift', function ($q) use ($date) {
                $q->whereDate('date', $date);
            })
            ->orderByDesc('logged_at')
            ->limit(10)
            ->get();

        $selectedProductionShiftId = $request->integer('production_shift_id') ?: null;

        return view('operations.production-entry.create', compact(
            'date',
            'productionShifts',
            'items',
            'recentLogs',
            'selectedProductionShiftId'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $uid = (int) auth()->id();
        $data = $request->validate([
            'production_shift_id' => ['required', 'exists:production_shifts,id'],
            'item_id' => ['required', Rule::exists('items', 'id')->where('user_id', $uid)],
            'quantity' => ['required', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'scrap_reason' => ['nullable', 'string', 'max:100'],
            'downtime_reason' => ['nullable', 'string', 'in:electricity,machine_failure,maintenance,other'],
            'downtime_lost_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'logged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($data['logged_at'])) {
            $data['logged_at'] = now();
        }

        DB::transaction(function () use ($request, $data, $uid) {
            $log = ProductionLog::create([
                'production_shift_id' => $data['production_shift_id'],
                'item_id' => $data['item_id'],
                'quantity' => $data['quantity'],
                'rejected_quantity' => $data['rejected_quantity'] ?? 0,
                'scrap_reason' => $data['scrap_reason'] ?? null,
                'logged_at' => $data['logged_at'],
                'notes' => $data['notes'] ?? null,
                'downtime_reason' => $data['downtime_reason'] ?? null,
                'downtime_lost_hours' => isset($data['downtime_lost_hours']) ? (float) $data['downtime_lost_hours'] : null,
            ]);

            $employee = null;
            if ($request->user()) {
                $employee = Employee::where('linked_user_id', $request->user()->id)->first();
            }

            $item = Item::find($data['item_id']);
            $productionShift = ProductionShift::find($data['production_shift_id']);

            $quantity = (float) $data['quantity'];
            $scrap = (float) ($data['rejected_quantity'] ?? 0);
            $cost = (float) ($item?->cost ?? 0);

            $goodValue = $quantity * $cost;
            $scrapValue = $scrap * $cost;
            $totalValue = $goodValue + $scrapValue;

            $journalEntryId = null;

            if ($totalValue > 0) {
                $inventoryAccount = DefaultLedgerAccounts::inventoryRawMaterials();
                $finishedGoodsAccount = DefaultLedgerAccounts::inventoryFinishedGoods();
                $scrapAccount = DefaultLedgerAccounts::scrapExpense();

                $journalUserId = (int) ($request->user()?->employee?->user_id ?? $request->user()?->id ?? $uid);

                $entry = JournalEntry::create([
                    'user_id' => $journalUserId,
                    'date' => $data['logged_at'],
                    'reference' => 'PROD-' . $log->id,
                    'description' => 'تسجيل إنتاج للصنف ' . ($item?->code ?? '') . ' بواسطة ' . ($employee?->name ?? 'غير محدد'),
                    'total' => $totalValue,
                ]);

                // مدين: الإنتاج التام بقيمة الكمية الجيدة
                if ($goodValue > 0) {
                    $entry->items()->create([
                        'account_id' => $finishedGoodsAccount->id,
                        'description' => 'إنتاج تام للصنف ' . ($item?->code ?? ''),
                        'debit' => $goodValue,
                        'credit' => 0,
                    ]);
                }

                // مدين: الهالك إن وجد
                if ($scrapValue > 0) {
                    $entry->items()->create([
                        'account_id' => $scrapAccount->id,
                        'description' => 'هالك للصنف ' . ($item?->code ?? ''),
                        'debit' => $scrapValue,
                        'credit' => 0,
                    ]);
                }

                // دائن: المخزون (خامات)
                $entry->items()->create([
                    'account_id' => $inventoryAccount->id,
                    'description' => 'صرف خامات للصنف ' . ($item?->code ?? ''),
                    'debit' => 0,
                    'credit' => $totalValue,
                ]);

                $journalEntryId = $entry->id;
            }

            ProductionRecord::create([
                'employee_id' => $employee?->id,
                'production_shift_id' => $productionShift?->id,
                'item_id' => $item?->id,
                'quantity' => $quantity,
                'scrap_quantity' => $scrap,
                'scrap_reason' => $data['scrap_reason'] ?? null,
                'recorded_at' => $data['logged_at'],
                'journal_entry_id' => $journalEntryId,
                'notes' => $data['notes'] ?? null,
                'downtime_reason' => $data['downtime_reason'] ?? null,
                'downtime_lost_hours' => isset($data['downtime_lost_hours']) ? (float) $data['downtime_lost_hours'] : null,
            ]);
        });

        return redirect()
            ->back()
            ->with('success', 'تم تسجيل الإنتاج بنجاح.');
    }

    /**
     * البحث عن صنف بالباركود (للمسح التلقائي).
     */
    public function itemByBarcode(Request $request): JsonResponse
    {
        $barcode = $request->input('barcode', '');
        $barcode = trim((string) $barcode);
        if ($barcode === '') {
            return response()->json(['found' => false]);
        }
        $item = Item::active()->byBarcode($barcode)->first();
        if (!$item) {
            return response()->json(['found' => false]);
        }
        return response()->json([
            'found' => true,
            'id' => $item->id,
            'code' => $item->code,
            'name_ar' => $item->name_ar,
        ]);
    }
}

