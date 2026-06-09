<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use App\Models\PosSale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class FleetStoreOrderPoolService
{
    public function __construct(
        private readonly FleetStoreOrderService $storeOrders,
    ) {}

    public function pendingCount(int $tenantUserId): int
    {
        return (int) PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->where('fulfillment_mode', PosSale::FULFILLMENT_FIELD_DELIVERY)
            ->where('fulfillment_status', PosSale::FULFILLMENT_STATUS_PENDING)
            ->count();
    }

    public function paginatedPending(int $tenantUserId, int $perPage = 20): LengthAwarePaginator
    {
        return PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('sale_channel', PosSale::CHANNEL_ONLINE_STORE)
            ->where('fulfillment_mode', PosSale::FULFILLMENT_FIELD_DELIVERY)
            ->where('fulfillment_status', PosSale::FULFILLMENT_STATUS_PENDING)
            ->with(['items.product'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, FleetRoute>
     */
    public function assignableRoutes(int $tenantUserId, ?int $agentId = null): Collection
    {
        return FleetRoute::query()
            ->with('agent:id,name')
            ->where('user_id', $tenantUserId)
            ->when($agentId !== null && $agentId > 0, fn ($q) => $q->where('agent_id', $agentId))
            ->whereIn('status', [FleetRoute::STATUS_PLANNED, FleetRoute::STATUS_IN_PROGRESS])
            ->orderByDesc('route_date')
            ->limit(40)
            ->get(['id', 'agent_id', 'route_date', 'status']);
    }

    public function assignToRoute(int $tenantUserId, PosSale $sale, int $routeId, ?int $agentId = null): FleetRouteStop
    {
        if (! $sale->isInFleetPool()) {
            throw new InvalidArgumentException('الطلب غير متاح في Pool أو مُسند مسبقاً.');
        }

        $route = FleetRoute::query()
            ->where('user_id', $tenantUserId)
            ->where('id', $routeId)
            ->first();

        if ($route === null) {
            throw new InvalidArgumentException('خط السير غير موجود.');
        }

        if (! in_array($route->status, [FleetRoute::STATUS_PLANNED, FleetRoute::STATUS_IN_PROGRESS], true)) {
            throw new InvalidArgumentException('لا يمكن إسناد طلب لخط سير مغلق.');
        }

        if ($agentId !== null && $agentId > 0 && (int) $route->agent_id !== $agentId) {
            throw new InvalidArgumentException('خط السير لا يتبع المندوب المحدد.');
        }

        return DB::transaction(function () use ($tenantUserId, $sale, $route): FleetRouteStop {
            $customer = $this->storeOrders->upsertCustomerFromOrder($tenantUserId, $sale);

            $maxOrder = (int) FleetRouteStop::query()
                ->where('route_id', $route->id)
                ->max('sort_order');

            $stop = FleetRouteStop::query()->create([
                'user_id' => $tenantUserId,
                'route_id' => $route->id,
                'customer_id' => $customer->id,
                'pos_sale_id' => $sale->id,
                'sort_order' => $maxOrder + 1,
                'status' => FleetRouteStop::STATUS_PENDING,
                'notes' => 'طلب متجر #'.($sale->invoice_number ?? $sale->id),
            ]);

            $sale->forceFill([
                'fulfillment_status' => PosSale::FULFILLMENT_STATUS_ASSIGNED,
                'assigned_agent_id' => $route->agent_id,
                'fleet_customer_id' => $customer->id,
            ])->save();

            return $stop->fresh(['customer:id,name,phone', 'route:id,route_date,agent_id']);
        });
    }

    public function assignToAgent(int $tenantUserId, PosSale $sale, int $agentId): PosSale
    {
        if (! $sale->isInFleetPool()) {
            throw new InvalidArgumentException('الطلب غير متاح في Pool.');
        }

        $exists = \App\Models\Fleet\FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('id', $agentId)
            ->where('status', \App\Models\Fleet\FleetAgent::STATUS_ACTIVE)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('المندوب غير موجود أو غير نشط.');
        }

        $customer = $this->storeOrders->upsertCustomerFromOrder($tenantUserId, $sale);
        $customer->update(['assigned_agent_id' => $agentId]);

        $sale->forceFill([
            'assigned_agent_id' => $agentId,
            'fleet_customer_id' => $customer->id,
        ])->save();

        return $sale->fresh(['items.product']);
    }
}
