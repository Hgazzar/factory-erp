<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Item;
use App\Models\ItemBomComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemBomController extends Controller
{
    /**
     * مزامنة وصفة التصنيع: حذف المكونات السابقة للمنتج التام وإدراج الصفوف الجديدة.
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        if ($item->type !== Item::TYPE_FINISHED_GOOD) {
            abort(403);
        }

        $validated = $request->validate([
            'components' => ['nullable', 'array'],
            'components.*.component_item_id' => ['required', 'integer', 'exists:items,id'],
            'components.*.quantity_per_unit' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $rows = $validated['components'] ?? [];
        $seen = [];

        foreach ($rows as $row) {
            $cid = (int) $row['component_item_id'];
            $comp = Item::query()->find($cid);
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

        $oldLines = ItemBomComponent::query()
            ->where('finished_item_id', $item->id)
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
            DB::transaction(function () use ($item, $rows) {
                ItemBomComponent::query()->where('finished_item_id', $item->id)->delete();

                foreach ($rows as $row) {
                    ItemBomComponent::create([
                        'finished_item_id' => $item->id,
                        'component_item_id' => (int) $row['component_item_id'],
                        'quantity_per_unit' => $row['quantity_per_unit'],
                    ]);
                }
            });
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
