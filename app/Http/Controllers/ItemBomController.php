<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\AuditTrail;
use App\Models\BomList;
use App\Models\Item;
use App\Models\ItemBomComponent;
use App\Services\Manufacturing\BomListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemBomController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly BomListService $bomLists,
    ) {}

    /**
     * مزامنة وصفة التصنيع عبر BomList النشطة (مصدر الحقيقة) + مرآة ItemBomComponent للواجهة.
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        if ($item->type !== Item::TYPE_FINISHED_GOOD) {
            abort(403);
        }

        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'components' => ['nullable', 'array'],
            'components.*.component_item_id' => ['required', 'integer', 'exists:items,id'],
            'components.*.quantity_per_unit' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $rows = $validated['components'] ?? [];
        $seen = [];

        foreach ($rows as $row) {
            $cid = (int) $row['component_item_id'];
            $comp = Item::query()
                ->withoutGlobalScopes()
                ->where('user_id', $tenantUserId)
                ->whereKey($cid)
                ->first();

            if (! $comp || $comp->type !== Item::TYPE_RAW_MATERIAL || ! $comp->is_active) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'يجب أن تكون كل المكونات من أصناف مواد خام نشطة.');
            }
            if (isset($seen[$cid])) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'لا تكرر نفس المادة الخام في أكثر من سطر؛ ادمج الكميات في سطر واحد.');
            }
            $seen[$cid] = true;
        }

        $oldLines = $item->bomComponents()
            ->with('componentItem:id,code,name_ar')
            ->get()
            ->map(fn ($c) => [
                'component_item_id' => $c->component_item_id,
                'code' => $c->componentItem?->code,
                'quantity_per_unit' => (string) $c->quantity_per_unit,
            ])
            ->values()
            ->all();

        try {
            if ($rows === []) {
                BomList::query()
                    ->withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->where('item_id', $item->id)
                    ->where('status', BomList::STATUS_ACTIVE)
                    ->update(['status' => BomList::STATUS_OBSOLETE]);

                ItemBomComponent::query()->where('finished_item_id', $item->id)->delete();
            } else {
                $this->bomLists->syncActiveBomFromItemComponents($tenantUserId, (int) $item->id, $rows);
            }
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $item->load('bomComponents.componentItem');
        $newLines = $item->bomComponents
            ->map(fn ($c) => [
                'component_item_id' => $c->component_item_id,
                'code' => $c->componentItem?->code,
                'quantity_per_unit' => (string) $c->quantity_per_unit,
            ])
            ->values()
            ->all();

        AuditTrail::log('update', 'bom', $item->id, ['lines' => $oldLines], ['lines' => $newLines]);

        return redirect()
            ->route('items.show', $item)
            ->with('success', 'تم حفظ مكونات التصنيع (BOM) بنجاح.');
    }
}
