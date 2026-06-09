<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Models\PosSale;
use App\Services\Fleet\FleetStoreOrderPoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetStoreOrderWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request, FleetStoreOrderPoolService $pool): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return view('fleet.store-orders.index', [
            'orders' => $pool->paginatedPending($tenantUserId),
            'pendingCount' => $pool->pendingCount($tenantUserId),
            'agents' => FleetAgent::query()
                ->where('user_id', $tenantUserId)
                ->where('status', FleetAgent::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name']),
            'routes' => $pool->assignableRoutes($tenantUserId),
            'fulfillmentLabels' => PosSale::fulfillmentStatusLabels(),
            'modeLabels' => PosSale::fulfillmentModeLabels(),
        ]);
    }

    public function assignRoute(Request $request, int $sale, FleetStoreOrderPoolService $pool): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $validated = $request->validate([
            'route_id' => ['required', 'integer', 'min:1'],
        ]);

        $posSale = $this->resolveStoreOrder($tenantUserId, $sale);

        try {
            $pool->assignToRoute($tenantUserId, $posSale, (int) $validated['route_id']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['assign' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إسناد الطلب لخط السير.');
    }

    public function assignAgent(Request $request, int $sale, FleetStoreOrderPoolService $pool): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $validated = $request->validate([
            'agent_id' => ['required', 'integer', 'min:1'],
        ]);

        $posSale = $this->resolveStoreOrder($tenantUserId, $sale);

        try {
            $pool->assignToAgent($tenantUserId, $posSale, (int) $validated['agent_id']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['assign' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إسناد الطلب للمندوب — يمكنك لاحقاً إضافته لخط سير.');
    }

    private function resolveStoreOrder(int $tenantUserId, int $saleId): PosSale
    {
        /** @var PosSale|null $sale */
        $sale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($saleId)
            ->first();

        if ($sale === null) {
            abort(404);
        }

        return $sale;
    }
}
