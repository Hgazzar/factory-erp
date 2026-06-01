<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\BomList;
use App\Models\BomListLine;
use App\Models\Item;
use App\Services\Manufacturing\BomListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class BomListWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly BomListService $bomLists,
    ) {}

    public function index(): View
    {
        $lists = BomList::query()
            ->with(['finishedItem'])
            ->latest()
            ->paginate(20);

        return view('manufacturing.bom-lists.index', compact('lists'));
    }

    public function create(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $finishedGoods = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('type', Item::TYPE_FINISHED_GOOD)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar', 'unit']);

        $rawMaterials = Item::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('type', Item::TYPE_RAW_MATERIAL)
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar', 'unit']);

        return view('manufacturing.bom-lists.create', [
            'finishedGoods' => $finishedGoods,
            'rawMaterials' => $rawMaterials,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(function ($q) use ($tenantUserId) {
                    $q->where('user_id', $tenantUserId)->where('type', Item::TYPE_FINISHED_GOOD);
                }),
            ],
            'name' => 'required|string|max:255',
            'version' => 'required|string|max:40',
            'status' => ['required', Rule::in([BomList::STATUS_DRAFT, BomList::STATUS_ACTIVE, BomList::STATUS_OBSOLETE])],
            'labor_cost' => 'nullable|numeric|min:0',
            'overhead_cost' => 'nullable|numeric|min:0',
            'header_notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.component_item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(function ($q) use ($tenantUserId) {
                    $q->where('user_id', $tenantUserId)->where('type', Item::TYPE_RAW_MATERIAL);
                }),
            ],
            'lines.*.quantity' => 'required|numeric|min:0.0001',
            'lines.*.unit' => 'nullable|string|max:50',
            'lines.*.scrap_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.notes' => 'nullable|string|max:500',
        ]);

        $bom = DB::transaction(function () use ($tenantUserId, $validated) {
            $bom = BomList::query()->create([
                'user_id' => $tenantUserId,
                'item_id' => (int) $validated['item_id'],
                'name' => $validated['name'],
                'version' => $validated['version'],
                'status' => $validated['status'],
                'labor_cost' => (float) ($validated['labor_cost'] ?? 0),
                'overhead_cost' => (float) ($validated['overhead_cost'] ?? 0),
                'header_notes' => $validated['header_notes'] ?? null,
            ]);

            foreach ($validated['lines'] as $i => $row) {
                BomListLine::query()->create([
                    'bom_list_id' => $bom->id,
                    'component_item_id' => (int) $row['component_item_id'],
                    'quantity' => (float) $row['quantity'],
                    'unit' => $row['unit'] ?? null,
                    'scrap_percent' => (float) ($row['scrap_percent'] ?? 0),
                    'notes' => $row['notes'] ?? null,
                    'sort_order' => $i,
                ]);
            }

            $this->bomLists->afterBomListPersisted($bom->fresh(['lines']));

            return $bom;
        });

        return redirect()
            ->route('manufacturing.bom-lists.show', $bom)
            ->with('success', 'تم حفظ قائمة المواد.');
    }

    public function show(BomList $bom_list): View
    {
        $bom_list->load(['finishedItem', 'lines.componentItem']);

        return view('manufacturing.bom-lists.show', ['bom' => $bom_list]);
    }
}
