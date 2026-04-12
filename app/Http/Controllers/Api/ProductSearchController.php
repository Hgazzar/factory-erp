<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSearchController extends Controller
{
    /**
     * بحث الأصناف النشطة للاستخدام في عروض الأسعار وأوامر البيع (JSON).
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $query = Item::query()
            ->active()
            ->orderBy('code');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($qry) use ($like) {
                $qry->where('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('barcode', 'like', $like);
            });
        }

        $rows = $query->limit(40)->get();

        $data = $rows->map(function (Item $item) {
            $salePrice = (float) ($item->sale_price ?? 0);
            if ($salePrice <= 0) {
                $salePrice = (float) ($item->selling_price ?? 0);
            }

            return [
                'id' => $item->id,
                'code' => $item->code,
                'name_ar' => $item->name_ar,
                'name_en' => $item->name_en,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'unit' => $item->unit,
                'cost' => (float) ($item->cost ?? 0),
                'sale_price' => $salePrice,
                'selling_price' => (float) ($item->selling_price ?? 0),
                'display_name' => $item->display_name,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
