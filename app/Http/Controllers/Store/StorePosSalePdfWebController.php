<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Services\Store\StorePosSalePdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class StorePosSalePdfWebController extends Controller
{
    public function merchantReceipt(PosSale $posSale, StorePosSalePdfService $pdf): Response
    {
        abort_if((int) $posSale->user_id !== (int) auth()->id(), 403);
        abort_if($posSale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE, 404);

        return $pdf->streamReceipt($posSale, (int) auth()->id());
    }

    public function portalReceipt(Request $request, string $tenantSlug, int $saleId, StorePosSalePdfService $pdf): Response
    {
        $tenantUserId = (int) $request->attributes->get('store_portal_tenant_user_id');

        /** @var PosSale|null $sale */
        $sale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($saleId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->first();

        abort_if($sale === null, 404);

        return $pdf->streamReceipt($sale, $tenantUserId);
    }
}
