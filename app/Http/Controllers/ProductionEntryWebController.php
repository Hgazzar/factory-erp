<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ProductionLog;
use App\Models\ProductionRecord;
use App\Models\ProductionShift;
use App\Models\Warehouse;
use App\Services\FinancialRecordingService;
use App\Services\Hr\ProductionEntryAttendanceGuard;
use App\Services\Manufacturing\BomListService;
use App\Services\ProductionEntryInventoryService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\PremiumFeatureKeys;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ProductionEntryWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function create(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $date = $request->date('date', now()->toDateString());

        $productionShifts = ProductionShift::with(['shift', 'productionLine', 'machine'])
            ->whereDate('date', $date)
            ->orderBy('shift_id')
            ->get();

        $items = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->active()
            ->orderBy('code')
            ->get();

        $recentLogs = ProductionLog::with(['productionShift.shift', 'item'])
            ->whereHas('productionShift', function ($q) use ($date) {
                $q->whereDate('date', $date);
            })
            ->orderByDesc('logged_at')
            ->limit(10)
            ->get();

        $this->attachJournalMetaToLogs($recentLogs);

        $selectedProductionShiftId = $request->integer('production_shift_id') ?: null;

        $warehouses = Warehouse::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->active()
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'code']);

        $features = app(TenantFeatureRegistry::class);

        return view('operations.production-entry.create', [
            'date' => $date,
            'productionShifts' => $productionShifts,
            'items' => $items,
            'recentLogs' => $recentLogs,
            'selectedProductionShiftId' => $selectedProductionShiftId,
            'warehouses' => $warehouses,
            'warehouseRequired' => $features->isEnabled(
                PremiumFeatureKeys::MANUFACTURING_INVENTORY_AUTO_LINK,
                $tenantUserId
            ),
        ]);
    }

    public function store(
        Request $request,
        ProductionEntryInventoryService $productionEntryInventory,
        FinancialRecordingService $financialRecording,
        TenantFeatureRegistry $features,
        BomListService $bomLists,
        ProductionEntryAttendanceGuard $attendanceGuard,
    ): RedirectResponse {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $actingUserId = (int) auth()->id();
        $inventoryAutoLink = $features->isEnabled(
            PremiumFeatureKeys::MANUFACTURING_INVENTORY_AUTO_LINK,
            $tenantUserId
        );
        $machineDowntimeEnabled = $features->isEnabled(
            PremiumFeatureKeys::MANUFACTURING_MACHINE_DOWNTIME,
            $tenantUserId
        );

        $warehouseRules = $inventoryAutoLink
            ? ['required', 'integer', Rule::exists('warehouses', 'id')->where('user_id', $tenantUserId)]
            : ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('user_id', $tenantUserId)];

        $data = $request->validate([
            'production_shift_id' => [
                'required',
                Rule::exists('production_shifts', 'id')->where('user_id', $tenantUserId),
            ],
            'item_id' => [
                'required',
                Rule::exists('items', 'id')->where('user_id', $tenantUserId),
            ],
            'warehouse_id' => $warehouseRules,
            'quantity' => ['required', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'scrap_reason' => ['nullable', 'string', 'max:100'],
            'downtime_reason' => ['nullable', 'string', 'in:electricity,machine_failure,maintenance,other'],
            'downtime_lost_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'logged_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ], [
            'warehouse_id.required' => 'ميزة الربط المخزني المؤتمت مفعّلة — اختيار المستودع إلزامي.',
        ]);

        if (! $machineDowntimeEnabled) {
            $data['downtime_reason'] = null;
            $data['downtime_lost_hours'] = null;
        }

        if (empty($data['logged_at'])) {
            $data['logged_at'] = now();
        }

        try {
            $attendanceGuard->assertEligible(
                $request->user(),
                $tenantUserId,
                \Carbon\Carbon::parse($data['logged_at'])
            );
        } catch (RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $warehouseId = isset($data['warehouse_id']) && (int) $data['warehouse_id'] > 0
            ? (int) $data['warehouse_id']
            : null;

        $bomWarning = null;

        try {
            DB::transaction(function () use (
                $request,
                $data,
                $tenantUserId,
                $actingUserId,
                $warehouseId,
                $productionEntryInventory,
                $financialRecording,
                $bomLists,
                &$bomWarning,
            ): void {
                $log = ProductionLog::create([
                    'user_id' => $tenantUserId,
                    'production_shift_id' => $data['production_shift_id'],
                    'item_id' => $data['item_id'],
                    'warehouse_id' => $warehouseId,
                    'quantity' => $data['quantity'],
                    'rejected_quantity' => $data['rejected_quantity'] ?? 0,
                    'scrap_reason' => $data['scrap_reason'] ?? null,
                    'logged_at' => $data['logged_at'],
                    'notes' => $data['notes'] ?? null,
                    'downtime_reason' => $data['downtime_reason'] ?? null,
                    'downtime_lost_hours' => isset($data['downtime_lost_hours']) ? (float) $data['downtime_lost_hours'] : null,
                ]);

                $employee = Employee::query()
                    ->where('linked_user_id', $request->user()->id)
                    ->where('user_id', $tenantUserId)
                    ->first();

                $item = Item::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($data['item_id'])
                    ->first();

                $productionShift = ProductionShift::query()->whereKey($data['production_shift_id'])->first();

                $quantity = (float) $data['quantity'];
                $scrap = (float) ($data['rejected_quantity'] ?? 0);

                if ($warehouseId && $item && $item->type === Item::TYPE_FINISHED_GOOD && $quantity > 0) {
                    $bomWarning = $this->bomConsumptionWarning($bomLists, $tenantUserId, $item);
                }

                $inv = ['applied' => false, 'unit_batch_cost' => (float) ($item?->cost ?? 0), 'total_material_cost' => 0.0];
                if ($warehouseId && $item && $item->type === Item::TYPE_FINISHED_GOOD && $quantity > 0) {
                    $inv = $productionEntryInventory->applyInventoryForLog($log, $warehouseId);
                }

                $unitCost = (float) ($inv['unit_batch_cost'] ?? ($item?->cost ?? 0));
                if (! $inv['applied']) {
                    $unitCost = (float) ($item?->cost ?? 0);
                }

                $goodValue = $quantity * $unitCost;
                $scrapValue = $scrap * $unitCost;

                $journalEntryId = null;

                if ($warehouseId !== null && ($goodValue + $scrapValue) > 0) {
                    $entry = $financialRecording->recordProductionEntry(
                        $tenantUserId,
                        $actingUserId,
                        \Carbon\Carbon::parse($data['logged_at'])->toDateString(),
                        'PROD-'.$log->id,
                        'تسجيل إنتاج للصنف '.($item?->code ?? '').' بواسطة '.($employee?->name ?? 'غير محدد')
                            .($inv['applied'] ? ' (مرتبط بالمخزون/BOM)' : ''),
                        $goodValue,
                        $scrapValue,
                        (string) ($item?->code ?? ''),
                        $warehouseId,
                    );

                    $journalEntryId = $entry?->id;
                }

                ProductionRecord::create([
                    'user_id' => $tenantUserId,
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
        } catch (RuntimeException|\InvalidArgumentException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->back()->with('success', 'تم تسجيل الإنتاج بنجاح.');

        $warnings = array_filter([
            $bomWarning,
            $warehouseId === null ? 'لم يُختَر مستودع — لم يُرحَّل مخزون ولا قيد محاسبي.' : null,
        ]);

        if ($warnings !== []) {
            $redirect->with('warning', implode(' ', $warnings));
        }

        return $redirect;
    }

    /**
     * البحث عن صنف بالباركود (للمسح التلقائي).
     */
    public function itemByBarcode(Request $request): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $barcode = trim((string) $request->input('barcode', ''));
        if ($barcode === '') {
            return response()->json(['found' => false]);
        }

        $item = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->active()
            ->byBarcode($barcode)
            ->first();

        if (! $item) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'id' => $item->id,
            'code' => $item->code,
            'name_ar' => $item->name_ar,
        ]);
    }

    /**
     * حالة BOM للصنف — لتحذير الواجهة قبل الحفظ.
     */
    public function itemBomStatus(Request $request, BomListService $bomLists): JsonResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $itemId = $request->integer('item_id');

        if ($itemId < 1) {
            return response()->json(['show_warning' => false]);
        }

        $item = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($itemId)
            ->first(['id', 'type', 'code', 'name_ar']);

        if (! $item || $item->type !== Item::TYPE_FINISHED_GOOD) {
            return response()->json([
                'show_warning' => false,
                'is_finished_good' => $item?->type === Item::TYPE_FINISHED_GOOD,
            ]);
        }

        $warning = $this->bomConsumptionWarning($bomLists, $tenantUserId, $item);

        return response()->json([
            'show_warning' => $warning !== null,
            'message' => $warning,
            'is_finished_good' => true,
            'item_code' => $item->code,
        ]);
    }

    /**
     * @param  Collection<int, ProductionLog>  $logs
     */
    private function attachJournalMetaToLogs(Collection $logs): void
    {
        foreach ($logs as $log) {
            if (! $log->logged_at) {
                $log->setAttribute('linked_journal_entry_id', null);

                continue;
            }

            $record = ProductionRecord::query()
                ->withoutGlobalScopes()
                ->where('user_id', $log->user_id)
                ->where('production_shift_id', $log->production_shift_id)
                ->where('item_id', $log->item_id)
                ->where('quantity', $log->quantity)
                ->whereBetween('recorded_at', [
                    $log->logged_at->copy()->subSeconds(10),
                    $log->logged_at->copy()->addSeconds(10),
                ])
                ->orderByDesc('id')
                ->first(['journal_entry_id']);

            $log->setAttribute('linked_journal_entry_id', $record?->journal_entry_id);
        }
    }

    private function bomConsumptionWarning(BomListService $bomLists, int $tenantUserId, Item $item): ?string
    {
        if ($item->type !== Item::TYPE_FINISHED_GOOD) {
            return null;
        }

        $activeBom = $bomLists->activeBomForItem($tenantUserId, (int) $item->id);

        if ($activeBom !== null && $activeBom->lines->isNotEmpty()) {
            return null;
        }

        return 'الصنف «'.($item->code ?? '').'» ليس له قائمة مواد (BOM) نشطة — سيُزاد رصيد المنتج التام فقط ولن يُصرف خامات تلقائياً.';
    }
}
