<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\CompanySetting;
use App\Models\PosDevice;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Services\Clinic\ClinicPortalQrCodeService;
use App\Services\Pos\PosCatalogService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\PosFeatureKeys;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PosTerminalWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(
        Request $request,
        PosCatalogService $catalog,
        TenantFeatureRegistry $features,
    ): View {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $devices = PosDevice::query()
            ->where('user_id', $tenantUserId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $openSessions = PosSession::query()
            ->where('user_id', $tenantUserId)
            ->open()
            ->with(['posDevice:id,name'])
            ->latest('opened_at')
            ->get(['id', 'pos_device_id', 'employee_id', 'opened_at']);

        $selectedDeviceId = (int) ($request->query('device_id') ?: ($devices->first()?->id ?? 0));
        $selectedSession = $openSessions->firstWhere('pos_device_id', $selectedDeviceId);

        return view('pos.terminal.index', [
            'devices' => $devices,
            'openSessions' => $openSessions,
            'selectedDeviceId' => $selectedDeviceId > 0 ? $selectedDeviceId : null,
            'selectedSessionId' => $selectedSession?->id,
            'initialProducts' => $catalog->searchProducts($tenantUserId, null, null, 60),
            'initialCategories' => $catalog->activeCategories($tenantUserId),
            'canManualPrice' => $features->isEnabled(PosFeatureKeys::MANUAL_PRICE_OVERRIDE, $tenantUserId),
            'canMultiWarehouse' => $features->isEnabled(PosFeatureKeys::MULTI_WAREHOUSE, $tenantUserId),
            'currencyCode' => CompanySetting::resolvedCurrencyCode(),
        ]);
    }

    public function receipt(PosSale $posSale, ClinicPortalQrCodeService $qr): View
    {
        abort_if((int) $posSale->user_id !== $this->resolveOperationsTenantUserId(), 403);

        $posSale->load(['items.product', 'posDevice']);

        $verifyUrl = route('pos.terminal.receipt', $posSale);
        $qrDataUri = $qr->pngDataUri($verifyUrl);

        $company = CompanySetting::query()
            ->where('user_id', $posSale->user_id)
            ->first();

        return view('pos.terminal.receipt', [
            'sale' => $posSale,
            'company' => $company,
            'qrDataUri' => $qrDataUri,
            'currencyCode' => CompanySetting::resolvedCurrencyCode(),
        ]);
    }
}
