<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;
use InvalidArgumentException;

/**
 * تكلفة البيع من متوسط التكلفة المخزَّن على الصنف (items.cost)،
 * والذي يُحدَّث عند ترحيل الإنتاج اللحظي بمنطق unit_batch_cost في الإنتاج.
 */
final class PosCostingService
{
    /**
     * تكلفة وحدة منتج تام للبيع من نقطة البيع.
     *
     * @throws InvalidArgumentException إذا لم تُحدَّد تكلفة للصنف
     */
    public function unitCostForFinishedGoodSale(Item $item): float
    {
        if ($item->type !== Item::TYPE_FINISHED_GOOD) {
            throw new InvalidArgumentException('نقطة البيع تبيع منتجات تامة فقط.');
        }

        $cost = round((float) ($item->cost ?? 0), 4);
        if ($cost <= 0) {
            throw new InvalidArgumentException(
                'لم تُحدَّد تكلفة للصنف «'.($item->code).'». راجع ترحيل الإنتاج أو متوسط التكلفة في بطاقة الصنف.'
            );
        }

        return $cost;
    }
}
