<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PriceList;
use App\Models\PriceListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function index(Request $request): View
    {
        $query = PriceList::query()->withCount('items')->orderByDesc('priority')->orderBy('name');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $priceLists = $query->paginate(15)->withQueryString();

        return view('inventory.price-lists.index', [
            'priceLists' => $priceLists,
            'types' => PriceList::types(),
        ]);
    }

    public function create(): View
    {
        $items = Item::where('is_active', true)->orderBy('name_ar')->get(['id', 'code', 'name_ar', 'name_en', 'selling_price', 'cost']);
        $itemsForJs = $items->map(fn ($i) => [
            'id' => $i->id,
            'code' => $i->code,
            'name_ar' => $i->name_ar,
            'name_en' => $i->name_en,
            'selling_price' => (float) ($i->selling_price ?? 0),
            'cost' => (float) ($i->cost ?? 0),
        ])->values();
        return view('inventory.price-lists.create', [
            'types' => PriceList::types(),
            'pricingMethods' => PriceList::pricingMethods(),
            'items' => $items,
            'itemsForJs' => $itemsForJs,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:price_lists,code',
            'name' => 'required|string|max:255',
            'currency' => 'nullable|string|max:10',
            'type' => 'required|in:sale,purchase',
            'pricing_method' => 'nullable|in:fixed,margin',
            'default_margin_percent' => 'nullable|numeric|min:-99|max:999',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'priority' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'lines' => 'nullable|array',
            'lines.*.item_id' => 'required_with:lines|exists:items,id',
            'lines.*.price' => 'required_with:lines|numeric|min:0',
        ], [], [
            'code' => 'الرمز',
            'name' => 'الاسم',
            'type' => 'نوع القائمة',
            'lines.*.item_id' => 'الصنف',
            'lines.*.price' => 'السعر',
        ]);

        $lines = $request->input('lines', []);
        $notes = $validated['notes'] ?? null;
        if (empty(trim((string) $notes))) {
            $notes = $this->generateDescription(
                $validated['name'],
                $validated['type'],
                $validated['valid_from'] ?? null,
                $validated['valid_to'] ?? null,
                $request->input('default_margin_percent'),
                count($lines)
            );
        }

        $list = PriceList::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'currency' => $validated['currency'] ?? 'SAR',
            'type' => $validated['type'],
            'pricing_method' => $validated['pricing_method'] ?? 'fixed',
            'default_margin_percent' => $validated['default_margin_percent'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_to' => $validated['valid_to'] ?? null,
            'priority' => (int) ($validated['priority'] ?? 0),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
            'notes' => $notes,
        ]);

        $this->syncItems($list, $lines);
        if ($list->is_default) {
            PriceList::where('type', $list->type)->where('id', '!=', $list->id)->update(['is_default' => false]);
        }

        return redirect()->route('inventory.price-lists.index')
            ->with('success', 'تم إنشاء قائمة الأسعار بنجاح.');
    }

    public function edit(PriceList $priceList): View
    {
        $items = Item::where('is_active', true)->orderBy('name_ar')->get(['id', 'code', 'name_ar', 'name_en', 'selling_price', 'cost']);
        $priceList->load('items.item');
        return view('inventory.price-lists.edit', [
            'priceList' => $priceList,
            'types' => PriceList::types(),
            'pricingMethods' => PriceList::pricingMethods(),
            'items' => $items,
        ]);
    }

    public function update(Request $request, PriceList $priceList): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:price_lists,code,' . $priceList->id,
            'name' => 'required|string|max:255',
            'currency' => 'nullable|string|max:10',
            'type' => 'required|in:sale,purchase',
            'pricing_method' => 'nullable|in:fixed,margin',
            'default_margin_percent' => 'nullable|numeric|min:-99|max:999',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'priority' => 'nullable|integer|min:0',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'lines' => 'nullable|array',
            'lines.*.item_id' => 'required_with:lines|exists:items,id',
            'lines.*.price' => 'required_with:lines|numeric|min:0',
        ], [], [
            'code' => 'الرمز',
            'name' => 'الاسم',
            'type' => 'نوع القائمة',
            'lines.*.item_id' => 'الصنف',
            'lines.*.price' => 'السعر',
        ]);

        $priceList->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'currency' => $validated['currency'] ?? 'SAR',
            'type' => $validated['type'],
            'pricing_method' => $validated['pricing_method'] ?? 'fixed',
            'default_margin_percent' => $validated['default_margin_percent'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_to' => $validated['valid_to'] ?? null,
            'priority' => (int) ($validated['priority'] ?? 0),
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
            'notes' => $validated['notes'] ?? $priceList->notes,
        ]);

        $this->syncItems($priceList, $request->input('lines', []));
        if ($priceList->is_default) {
            PriceList::where('type', $priceList->type)->where('id', '!=', $priceList->id)->update(['is_default' => false]);
        }

        return redirect()->route('inventory.price-lists.index')
            ->with('success', 'تم تحديث قائمة الأسعار بنجاح.');
    }

    public function duplicate(PriceList $priceList): RedirectResponse
    {
        $newList = $priceList->replicate(['id']);
        $baseCode = preg_replace('/-نسخة\d*$/', '', $priceList->code);
        $n = 0;
        do {
            $n++;
            $newCode = $baseCode . '-نسخة' . ($n > 1 ? $n : '');
        } while (PriceList::where('code', $newCode)->exists());
        $newList->code = $newCode;
        $newList->name = $priceList->name . ' (نسخة' . ($n > 1 ? ' ' . $n : '') . ')';
        $newList->is_default = false;
        $newList->save();

        foreach ($priceList->items as $line) {
            PriceListItem::create([
                'price_list_id' => $newList->id,
                'item_id' => $line->item_id,
                'price' => $line->price,
            ]);
        }

        return redirect()->route('inventory.price-lists.edit', $newList)
            ->with('success', 'تم تكرار القائمة. يمكنك تعديل الرمز والاسم والأصناف.');
    }

    public function updateByPercent(Request $request, PriceList $priceList): RedirectResponse
    {
        $request->validate([
            'percent' => 'required|numeric|min:-99|max:999',
        ], [], ['percent' => 'النسبة المئوية']);

        $percent = (float) $request->percent;
        $multiplier = 1 + ($percent / 100);

        foreach ($priceList->items as $line) {
            $newPrice = round((float) $line->price * $multiplier, 4);
            $line->update(['price' => max(0, $newPrice)]);
        }

        $msg = $percent >= 0
            ? "تم زيادة أسعار القائمة بنسبة {$percent}%."
            : "تم تخفيض أسعار القائمة بنسبة " . abs($percent) . "%.";
        return redirect()->route('inventory.price-lists.edit', $priceList)->with('success', $msg);
    }

    private function syncItems(PriceList $list, array $lines): void
    {
        $list->items()->delete();
        $seen = [];
        foreach ($lines as $row) {
            $itemId = (int) ($row['item_id'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            if ($itemId && !isset($seen[$itemId])) {
                $seen[$itemId] = true;
                $list->items()->create(['item_id' => $itemId, 'price' => $price]);
            }
        }
    }

    /** وصف تلقائي للقائمة بناءً على محتواها */
    private function generateDescription(string $name, string $type, ?string $validFrom, ?string $validTo, $marginPercent, int $itemsCount): string
    {
        $typeLabel = $type === 'sale' ? 'البيع' : 'الشراء';
        $year = null;
        if ($validFrom) {
            $year = \Carbon\Carbon::parse($validFrom)->format('Y');
        } elseif ($validTo) {
            $year = \Carbon\Carbon::parse($validTo)->format('Y');
        } else {
            $year = now()->format('Y');
        }
        $parts = ["قائمة أسعار {$typeLabel} لعام {$year}"];
        if ($marginPercent !== null && $marginPercent !== '') {
            $p = (float) $marginPercent;
            if ($p > 0) {
                $parts[] = "تشمل هامش {$p}% على التكلفة";
            } elseif ($p < 0) {
                $parts[] = 'تشمل خصم ' . abs($p) . '% على التكلفة';
            }
        }
        if ($itemsCount > 0) {
            $parts[] = "تشمل {$itemsCount} صنفاً";
        }
        return implode('، ', $parts) . '.';
    }
}
