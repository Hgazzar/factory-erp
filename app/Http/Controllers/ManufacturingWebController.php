<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BomList;
use App\Models\BomListLine;
use App\Models\Item;
use App\Models\Machine;
use App\Models\ManufacturingRun;
use App\Models\Warehouse;
use App\Services\ManufacturingService;
use App\Services\ProductionVarianceReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class ManufacturingWebController extends Controller
{
    public function dashboard(): View
    {
        $uid = (int) auth()->id();

        $totalBoms = BomList::query()->count();

        $activeBoms = BomList::query()
            ->where('status', BomList::STATUS_ACTIVE)
            ->count();

        $totalWorkOrders = ManufacturingRun::query()->count();

        $inProgress = ManufacturingRun::query()
            ->where('status', ManufacturingRun::STATUS_DRAFT)
            ->count();

        $startOfMonth = now()->copy()->startOfMonth();
        $endOfMonth = now()->copy()->endOfMonth();

        $completedThisMonth = ManufacturingRun::query()
            ->where('status', ManufacturingRun::STATUS_POSTED)
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        $runsCreatedThisMonth = ManufacturingRun::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $postedTotal = ManufacturingRun::query()
            ->where('status', ManufacturingRun::STATUS_POSTED)
            ->count();

        $efficiencyPercent = $runsCreatedThisMonth > 0
            ? (int) min(100, round(100 * $completedThisMonth / $runsCreatedThisMonth))
            : ($totalWorkOrders > 0
                ? (int) min(100, round(100 * $postedTotal / $totalWorkOrders))
                : 0);

        $recentWorkOrders = ManufacturingRun::query()
            ->with(['finishedItem'])
            ->latest()
            ->limit(10)
            ->get();

        return view('manufacturing.dashboard', compact(
            'totalBoms',
            'activeBoms',
            'totalWorkOrders',
            'inProgress',
            'completedThisMonth',
            'efficiencyPercent',
            'recentWorkOrders',
        ));
    }

    public function index(): View
    {
        $runs = ManufacturingRun::query()
            ->with(['finishedItem', 'warehouse'])
            ->latest()
            ->paginate(20);

        return view('manufacturing.index', compact('runs'));
    }

    public function create(): View
    {
        $warehouses = Warehouse::query()->orderBy('name_ar')->get();
        $bomLists = BomList::query()
            ->where('status', BomList::STATUS_ACTIVE)
            ->with(['finishedItem', 'lines' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'), 'lines.componentItem'])
            ->orderBy('name')
            ->get();

        $machines = Machine::query()->active()->orderBy('name_ar')->get();

        $bomPayload = $bomLists->mapWithKeys(function (BomList $bom): array {
            return [
                (string) $bom->id => [
                    'finished_item_id' => $bom->item_id,
                    'product_label' => $bom->finishedItem
                        ? (($bom->finishedItem->code ? $bom->finishedItem->code.' — ' : '').$bom->finishedItem->name_ar)
                        : '',
                    'lines' => $bom->lines->map(function (BomListLine $line): array {
                        $item = $line->componentItem;

                        return [
                            'bom_list_line_id' => $line->id,
                            'ingredient_item_id' => $line->component_item_id,
                            'label' => ($item?->code ? $item->code.' — ' : '').($item?->name_ar ?? ''),
                            'quantity_per_fg' => (float) $line->quantity,
                            'scrap_percent' => (float) $line->scrap_percent,
                            'unit' => $line->unit ?? $item?->unit ?? '',
                        ];
                    })->values()->all(),
                ],
            ];
        })->all();

        return view('manufacturing.create', compact('warehouses', 'bomLists', 'machines', 'bomPayload'));
    }

    public function store(Request $request, ManufacturingService $manufacturingService): RedirectResponse
    {
        $uid = (int) $request->user()->id;

        $validated = $request->validate([
            'bom_list_id' => [
                'required',
                'integer',
                Rule::exists('bom_lists', 'id')->where(function ($q) use ($uid) {
                    $q->where('user_id', $uid)->where('status', BomList::STATUS_ACTIVE);
                }),
            ],
            'start_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')->where('user_id', $uid)],
            'machine_id' => ['nullable', 'integer', Rule::exists('machines', 'id')],
            'quantity_produced' => 'required|numeric|min:0.0001',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.bom_list_line_id' => ['required', 'integer'],
            'lines.*.ingredient_item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(function ($q) use ($uid) {
                    $q->where('user_id', $uid)
                        ->whereIn('type', [Item::TYPE_RAW_MATERIAL, Item::TYPE_FINISHED_GOOD]);
                }),
            ],
            'lines.*.quantity_consumed' => 'required|numeric|min:0.0001',
            'lines.*.actual_scrap_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        /** @var BomList $bom */
        $bom = BomList::query()
            ->whereKey((int) $validated['bom_list_id'])
            ->with(['lines' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])
            ->firstOrFail();

        if ($bom->lines->isEmpty()) {
            return back()->withInput()->withErrors([
                'bom_list_id' => 'قائمة المواد المختارة لا تحتوي مكوّنات.',
            ]);
        }

        $finishedId = (int) $bom->item_id;
        $byLineId = collect($validated['lines'])->keyBy(fn (array $r): int => (int) $r['bom_list_line_id']);

        foreach ($bom->lines as $bomLine) {
            $lid = (int) $bomLine->id;
            if (! $byLineId->has($lid)) {
                return back()->withInput()->withErrors([
                    'lines' => 'يجب أن تطابق أسطر النموذج أسطر قائمة المواد بالكامل دون إضافة أو حذف.',
                ]);
            }
            $row = $byLineId->get($lid);
            if ((int) $row['ingredient_item_id'] !== (int) $bomLine->component_item_id) {
                return back()->withInput()->withErrors([
                    'lines' => 'بيانات المكوّن لا تطابق قائمة المواد.',
                ]);
            }
            if ((int) $row['ingredient_item_id'] === $finishedId) {
                return back()->withInput()->withErrors([
                    'lines' => 'لا يمكن أن يكون المكوّن نفس المنتج التام لقائمة المواد.',
                ]);
            }
        }

        if ($byLineId->count() !== $bom->lines->count()) {
            return back()->withInput()->withErrors([
                'lines' => 'عدد الأسطر يجب أن يساوي أسطر قائمة المواد فقط.',
            ]);
        }

        $woQty = (float) $validated['quantity_produced'];
        $linesForService = [];
        foreach ($bom->lines as $bomLine) {
            $row = $byLineId->get((int) $bomLine->id);
            $planned = ManufacturingService::plannedConsumptionFromBomLine(
                (float) $bomLine->quantity,
                (float) $bomLine->scrap_percent,
                $woQty
            );
            $actualScrap = $row['actual_scrap_percent'] ?? null;
            $linesForService[] = [
                'bom_list_line_id' => (int) $bomLine->id,
                'ingredient_item_id' => (int) $bomLine->component_item_id,
                'quantity' => (float) $row['quantity_consumed'],
                'planned_quantity' => $planned,
                'planned_scrap_percent' => (float) $bomLine->scrap_percent,
                'actual_scrap_percent' => $actualScrap !== null && $actualScrap !== '' ? (float) $actualScrap : (float) $bomLine->scrap_percent,
            ];
        }

        try {
            $run = $manufacturingService->storeDraft($uid, [
                'production_date' => $validated['start_date'],
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'] ?? null,
                'warehouse_id' => (int) $validated['warehouse_id'],
                'finished_item_id' => $finishedId,
                'bom_list_id' => (int) $validated['bom_list_id'],
                'machine_id' => isset($validated['machine_id']) ? (int) $validated['machine_id'] : null,
                'quantity_produced' => $woQty,
                'notes' => $validated['notes'] ?? null,
                'lines' => $linesForService,
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('manufacturing.show', $run)
            ->with('success', 'تم حفظ مسودة أمر العمل. راجع البيانات ثم اضغط «ترحيل» لتطبيق المخزون والقيد.');
    }

    public function show(ManufacturingRun $manufacturing_run): View
    {
        $manufacturing_run->load([
            'lines.ingredientItem',
            'lines.bomListLine',
            'finishedItem',
            'warehouse',
            'bomList',
            'machine',
            'journalEntry',
        ]);

        return view('manufacturing.show', ['run' => $manufacturing_run]);
    }

    public function post(ManufacturingRun $manufacturing_run, ManufacturingService $manufacturingService): RedirectResponse
    {
        try {
            $manufacturingService->post($manufacturing_run, (int) auth()->id());
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('manufacturing.show', $manufacturing_run)
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('manufacturing.show', $manufacturing_run)
            ->with('success', 'تم ترحيل أمر العمل: صرف المدخلات، إضافة المنتج التام، وقيد المخزون مع تحديث أرصدة الدليل.');
    }

    public function destroy(ManufacturingRun $manufacturing_run): RedirectResponse
    {
        if (! $manufacturing_run->isDraft()) {
            return redirect()
                ->route('manufacturing.show', $manufacturing_run)
                ->with('error', 'لا يمكن حذف أمر عمل تم ترحيله محاسبياً.');
        }

        $manufacturing_run->delete();

        return redirect()->route('manufacturing.dashboard')->with('success', 'تم حذف مسودة أمر العمل.');
    }

    public function productionVarianceReport(Request $request): View
    {
        $uid = (int) auth()->id();

        $query = ManufacturingRun::query()
            ->with(['finishedItem', 'machine', 'warehouse', 'bomList', 'lines.ingredientItem', 'lines.bomListLine']);

        if ($request->filled('date_from')) {
            $query->whereDate('production_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('production_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('finished_item_id')) {
            $query->where('finished_item_id', (int) $request->input('finished_item_id'));
        }
        if ($request->filled('machine_id')) {
            $query->where('machine_id', (int) $request->input('machine_id'));
        }

        $runs = $query
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(static fn (ManufacturingRun $run): array => ProductionVarianceReportService::summarize($run));

        $filterFinishedItems = Item::query()
            ->where('user_id', $uid)
            ->where('type', Item::TYPE_FINISHED_GOOD)
            ->orderBy('code')
            ->get(['id', 'code', 'name_ar']);

        $filterMachines = Machine::query()->active()->orderBy('name_ar')->get(['id', 'code', 'name_ar']);

        $finishedItemOptions = $filterFinishedItems->map(fn (Item $it) => [
            'value' => (string) $it->id,
            'label' => trim((string) ($it->code ? $it->code.' — ' : '').(string) ($it->name_ar ?? '')),
        ])->all();

        $machineOptions = $filterMachines->map(fn (Machine $m) => [
            'value' => (string) $m->id,
            'label' => trim((string) ($m->code ? $m->code.' — ' : '').(string) ($m->name_ar ?? '')),
        ])->all();

        return view('manufacturing.reports.production-variance', [
            'runs' => $runs,
            'finishedItemOptions' => $finishedItemOptions,
            'machineOptions' => $machineOptions,
        ]);
    }
}
