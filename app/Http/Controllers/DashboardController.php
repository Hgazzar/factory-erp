<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\InstalledAsset;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Payment;
use App\Models\ProductionRecord;
use App\Models\ProductionShift;
use App\Models\Receipt;
use App\Models\SalesPayment;
use App\Models\ServiceOrder;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\LedgerAccountBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $viewerId = (int) ($request->user()?->id ?? 0);
        $systemWide = $viewerId === 1;
        $tenantUserId = (int) ($request->user()?->employee?->user_id ?? $viewerId);

        $productionForTenant = static function ($query) use ($systemWide, $tenantUserId, $viewerId): void {
            if ($systemWide || $viewerId === 0) {
                return;
            }
            $query->whereHas('employee', fn ($e) => $e->where('user_id', $tenantUserId));
        };

        // ─── إحصائيات اليوم (للقسم "Today's Statistics") ───
        $totalProductionToday = self::safeFloat(fn () => (float) ProductionRecord::query()
            ->whereDate('recorded_at', $today)
            ->tap($productionForTenant)
            ->sum('quantity'));
        $totalScrapToday = self::safeFloat(fn () => (float) ProductionRecord::query()
            ->whereDate('recorded_at', $today)
            ->tap($productionForTenant)
            ->sum('scrap_quantity'));
        $productionRecordsToday = self::safeInt(fn () => ProductionRecord::query()
            ->whereDate('recorded_at', $today)
            ->tap($productionForTenant)
            ->count());
        $scrapEntriesToday = self::safeInt(fn () => ProductionRecord::query()
            ->whereDate('recorded_at', $today)
            ->where('scrap_quantity', '>', 0)
            ->tap($productionForTenant)
            ->count());
        $productionOrdersToday = self::safeInt(function () use ($today, $systemWide, $viewerId, $productionForTenant) {
            if ($systemWide) {
                return (int) ProductionShift::whereDate('date', $today)->count();
            }
            if ($viewerId === 0) {
                return 0;
            }

            $shiftIds = ProductionRecord::query()
                ->whereDate('recorded_at', $today)
                ->tap($productionForTenant)
                ->whereNotNull('production_shift_id')
                ->distinct()
                ->pluck('production_shift_id');

            return $shiftIds->unique()->filter()->count();
        });
        $journalEntriesToday = self::safeInt(function () use ($today, $systemWide, $viewerId) {
            $q = JournalEntry::query()->whereDate('date', $today);
            if (! $systemWide && $viewerId !== 0) {
                $accountIds = Account::withoutGlobalScopes()->where('user_id', $viewerId)->pluck('id');
                if ($accountIds->isEmpty()) {
                    return 0;
                }
                $q->whereHas('items', fn ($qi) => $qi->whereIn('account_id', $accountIds));
            }

            return (int) $q->count();
        });

        $totalExpensesToday = self::safeFloat(function () use ($today, $systemWide) {
            $expenseAccountIds = $systemWide
                ? Account::withoutGlobalScopes()->where('type', Account::TYPE_EXPENSE)->pluck('id')
                : Account::query()->where('type', Account::TYPE_EXPENSE)->pluck('id');

            if ($expenseAccountIds->isEmpty()) {
                return 0.0;
            }

            return (float) JournalItem::query()
                ->select('journal_items.debit')
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_items.journal_entry_id')
                ->whereIn('journal_items.account_id', $expenseAccountIds)
                ->whereDate('journal_entries.date', $today)
                ->sum('journal_items.debit');
        });

        // بيانات الرسم البياني (آخر 7 أيام)
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $labels[] = $date->format('Y-m-d');
            $data[] = self::safeFloat(fn () => (float) ProductionRecord::query()
                ->whereDate('recorded_at', $date)
                ->tap($productionForTenant)
                ->sum('quantity'));
        }

        $rmCode = (string) config('accounting.raw_materials_inventory_code', '1041');
        $fgCode = (string) config('accounting.finished_goods_inventory_code', '1042');
        $inventoryRawMaterials = self::safeFloat(fn () => $systemWide
            ? LedgerAccountBalance::sumForAccountCodeAcrossUsers($rmCode)
            : LedgerAccountBalance::forAccountCode($rmCode));
        $inventoryFinishedGoods = self::safeFloat(fn () => $systemWide
            ? LedgerAccountBalance::sumForAccountCodeAcrossUsers($fgCode)
            : LedgerAccountBalance::forAccountCode($fgCode));
        $inventoryValueTotal = $inventoryRawMaterials + $inventoryFinishedGoods;

        $cashFlowLabels = [];
        $cashFlowIn = [];
        $cashFlowOut = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $cashFlowLabels[] = $d->format('m-d');
            $in = self::safeFloat(function () use ($d, $systemWide, $viewerId) {
                $receiptSum = Receipt::query()
                    ->whereDate('date', $d)
                    ->when(! $systemWide && $viewerId !== 0, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('user_id', $viewerId)))
                    ->sum('amount');
                $salesPaySum = SalesPayment::query()
                    ->whereDate('date', $d)
                    ->when(! $systemWide && $viewerId !== 0, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('user_id', $viewerId)))
                    ->sum('amount');

                return (float) $receiptSum + (float) $salesPaySum;
            });
            $out = self::safeFloat(function () use ($d, $systemWide, $viewerId) {
                if ($systemWide) {
                    return (float) Payment::withoutGlobalScopes()->whereDate('date', $d)->sum('amount');
                }

                return (float) Payment::query()->whereDate('date', $d)->sum('amount');
            });
            $cashFlowIn[] = round($in, 2);
            $cashFlowOut[] = round($out, 2);
        }

        $serviceOpenCount = 0;
        $serviceUrgentCount = 0;
        $serviceWarrantyExpiringCount = 0;
        $technicianMyOpenServiceCount = 0;
        if ($request->user()?->isAdminOrSuperAdmin()) {
            $serviceTenant = function ($q) use ($systemWide, $viewerId) {
                if (! $systemWide && $viewerId !== 0) {
                    $q->whereHas('customer', fn ($c) => $c->where('user_id', $viewerId));
                }
            };
            $serviceOpenCount = self::safeInt(fn () => ServiceOrder::query()
                ->tap($serviceTenant)
                ->whereIn('status', [
                    ServiceOrder::STATUS_OPEN,
                    ServiceOrder::STATUS_ASSIGNED,
                    ServiceOrder::STATUS_IN_PROGRESS,
                ])
                ->count());
            $serviceUrgentCount = self::safeInt(fn () => ServiceOrder::query()
                ->tap($serviceTenant)
                ->where('priority', ServiceOrder::PRIORITY_URGENT)
                ->whereIn('status', [
                    ServiceOrder::STATUS_OPEN,
                    ServiceOrder::STATUS_ASSIGNED,
                    ServiceOrder::STATUS_IN_PROGRESS,
                ])
                ->count());
            $serviceWarrantyExpiringCount = self::safeInt(fn () => InstalledAsset::query()
                ->when(! $systemWide && $viewerId !== 0, fn ($q) => $q->whereHas(
                    'deliveryOrder.salesOrder',
                    fn ($sq) => $sq->where('user_id', $viewerId)
                ))
                ->whereNotNull('warranty_end')
                ->whereDate('warranty_end', '>=', $today->toDateString())
                ->whereDate('warranty_end', '<=', $today->copy()->addDays(30)->toDateString())
                ->count());
        }
        if ($request->user()?->is_technician && ! $request->user()?->isAdminOrSuperAdmin()) {
            $technicianMyOpenServiceCount = self::safeInt(fn () => ServiceOrder::query()
                ->where('assigned_technician_id', $request->user()->id)
                ->whereNotIn('status', [ServiceOrder::STATUS_COMPLETED, ServiceOrder::STATUS_CANCELLED])
                ->count());
        }

        // ─── أعداد الوحدات (للعناوين والكروت) ───
        $stats = [
            'totalProductionToday' => $totalProductionToday,
            'totalScrapToday' => $totalScrapToday,
            'totalExpensesToday' => $totalExpensesToday,
            'productionRecordsToday' => $productionRecordsToday,
            'scrapEntriesToday' => $scrapEntriesToday,
            'productionOrdersToday' => $productionOrdersToday,
            'journalEntriesToday' => $journalEntriesToday,
            'chartLabels' => $labels,
            'chartData' => $data,
            'inventoryRawMaterials' => $inventoryRawMaterials,
            'inventoryFinishedGoods' => $inventoryFinishedGoods,
            'inventoryValueTotal' => $inventoryValueTotal,
            'inventoryRmCode' => $rmCode,
            'inventoryFgCode' => $fgCode,
            'cashFlowLabels' => $cashFlowLabels,
            'cashFlowIn' => $cashFlowIn,
            'cashFlowOut' => $cashFlowOut,
            'serviceOpenCount' => $serviceOpenCount,
            'serviceUrgentCount' => $serviceUrgentCount,
            'serviceWarrantyExpiringCount' => $serviceWarrantyExpiringCount,
            'technicianMyOpenServiceCount' => $technicianMyOpenServiceCount,
            'countItems' => self::safeInt(fn () => $systemWide
                ? Item::withoutGlobalScopes()->count()
                : Item::query()->count()),
            'countWarehouses' => self::safeInt(fn () => $systemWide
                ? Warehouse::withoutGlobalScopes()->count()
                : Warehouse::query()->count()),
            'countAccounts' => self::safeInt(fn () => $systemWide
                ? Account::withoutGlobalScopes()->count()
                : Account::query()->count()),
            'countSuppliers' => self::safeInt(fn () => $systemWide
                ? \App\Models\Supplier::withoutGlobalScopes()->count()
                : \App\Models\Supplier::query()->count()),
            'countCustomers' => self::safeInt(fn () => $systemWide
                ? \App\Models\Customer::withoutGlobalScopes()->count()
                : \App\Models\Customer::query()->count()),
            'countJournalEntries' => self::safeInt(function () use ($systemWide, $viewerId) {
                $q = JournalEntry::query();
                if (! $systemWide && $viewerId !== 0) {
                    $accountIds = Account::withoutGlobalScopes()->where('user_id', $viewerId)->pluck('id');
                    if ($accountIds->isEmpty()) {
                        return 0;
                    }
                    $q->whereHas('items', fn ($qi) => $qi->whereIn('account_id', $accountIds));
                }

                return (int) $q->count();
            }),
            'countProductionShifts' => self::safeInt(function () use ($systemWide, $viewerId, $productionForTenant) {
                if ($systemWide) {
                    return (int) ProductionShift::count();
                }
                if ($viewerId === 0) {
                    return 0;
                }
                $shiftIds = ProductionRecord::query()
                    ->tap($productionForTenant)
                    ->whereNotNull('production_shift_id')
                    ->distinct()
                    ->pluck('production_shift_id');

                return (int) $shiftIds->unique()->filter()->count();
            }),
            'countEmployees' => self::safeInt(fn () => \App\Models\Employee::query()
                ->when(! $systemWide && $viewerId !== 0, fn ($q) => $q->where('user_id', $viewerId))
                ->where('status', 'active')
                ->count()),
            'dashboardSystemWideSummary' => $systemWide,
        ];

        // ─── البحث العام (من شريط البحث في الهيدر) ───
        $searchQuery = $request->input('q');
        $searchResults = null;
        if ($searchQuery !== null && $searchQuery !== '') {
            $term = '%'.trim($searchQuery).'%';
            $searchResults = rescue(
                function () use ($term, $systemWide) {
                    $itemsQuery = $systemWide ? Item::withoutGlobalScopes() : Item::query();
                    $warehousesQuery = $systemWide ? Warehouse::withoutGlobalScopes() : Warehouse::query();

                    return [
                        'items' => $itemsQuery
                            ->where(function ($q) use ($term) {
                                $q->where('code', 'like', $term)
                                    ->orWhere('name_ar', 'like', $term)
                                    ->orWhere('name_en', 'like', $term)
                                    ->orWhere('barcode', 'like', $term);
                            })
                            ->limit(15)
                            ->get(),
                        'units' => Unit::query()
                            ->where(function ($q) use ($term) {
                                $q->where('code', 'like', $term)
                                    ->orWhere('name_ar', 'like', $term)
                                    ->orWhere('name_en', 'like', $term);
                            })
                            ->limit(10)
                            ->get(),
                        'warehouses' => $warehousesQuery
                            ->where(function ($q) use ($term) {
                                $q->where('code', 'like', $term)
                                    ->orWhere('name_ar', 'like', $term)
                                    ->orWhere('name_en', 'like', $term);
                            })
                            ->limit(10)
                            ->get(),
                    ];
                },
                [
                    'items' => collect(),
                    'units' => collect(),
                    'warehouses' => collect(),
                ],
                report: true
            );
        }

        $recentActivity = self::safeCollection(function () use ($viewerId) {
            return AuditTrail::query()
                ->whereNotIn('table_name', ['journal_entries', 'journal_items'])
                ->where('user_id', $viewerId)
                ->with('user:id,name')
                ->latest()
                ->limit(10)
                ->get();
        });

        return view('dashboard', array_merge($stats, [
            'searchQuery' => $searchQuery,
            'searchResults' => $searchResults,
            'recentActivity' => $recentActivity,
        ]));
    }

    private static function safeFloat(callable $callback): float
    {
        return (float) rescue($callback, 0.0, report: true);
    }

    private static function safeInt(callable $callback): int
    {
        return (int) rescue($callback, 0, report: true);
    }

    /**
     * @param  callable(): Collection<int, mixed>  $callback
     * @return Collection<int, mixed>
     */
    private static function safeCollection(callable $callback): Collection
    {
        /** @var Collection<int, mixed> */
        return rescue($callback, Collection::make(), report: true);
    }
}
