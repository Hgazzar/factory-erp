<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetProduct;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class FleetAgentMobileService
{
    public function __construct(
        private readonly FleetCustodyBalanceService $custodyBalances,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(int $tenantUserId, FleetAgent $agent): array
    {
        $today = now()->toDateString();

        $routesToday = FleetRoute::query()
            ->where('user_id', $tenantUserId)
            ->where('agent_id', $agent->id)
            ->whereDate('route_date', $today)
            ->whereIn('status', [FleetRoute::STATUS_PLANNED, FleetRoute::STATUS_IN_PROGRESS])
            ->count();

        $balanceLines = $this->custodyBalances->linesForAgent($tenantUserId, (int) $agent->id);

        return [
            'routes_today' => $routesToday,
            'custody_sku_count' => $balanceLines->count(),
            'custody_total_qty' => round((float) $balanceLines->sum('quantity'), 4),
            'collections_today' => (int) FleetCollection::query()
                ->where('user_id', $tenantUserId)
                ->where('agent_id', $agent->id)
                ->where('status', FleetCollection::STATUS_CONFIRMED)
                ->whereDate('collected_on', $today)
                ->count(),
        ];
    }

    /**
     * @return Collection<int, FleetRoute>
     */
    public function routesForAgent(int $tenantUserId, FleetAgent $agent, ?string $date = null): Collection
    {
        $date = trim((string) ($date ?? now()->toDateString()));

        return FleetRoute::query()
            ->withCount('stops')
            ->where('user_id', $tenantUserId)
            ->where('agent_id', $agent->id)
            ->whereDate('route_date', $date)
            ->whereIn('status', [
                FleetRoute::STATUS_PLANNED,
                FleetRoute::STATUS_IN_PROGRESS,
                FleetRoute::STATUS_COMPLETED,
            ])
            ->orderBy('route_date')
            ->orderBy('id')
            ->get();
    }

    public function routeForAgent(int $tenantUserId, FleetAgent $agent, int $routeId): FleetRoute
    {
        $route = FleetRoute::query()
            ->with([
                'stops.customer:id,name,phone,address,city',
                'stops.posSale:id,invoice_number,total_amount,fulfillment_status',
            ])
            ->where('user_id', $tenantUserId)
            ->where('agent_id', $agent->id)
            ->whereKey($routeId)
            ->first();

        if ($route === null) {
            throw new InvalidArgumentException('خط السير غير موجود.');
        }

        return $route;
    }

    public function stopForAgent(int $tenantUserId, FleetAgent $agent, int $stopId): FleetRouteStop
    {
        $stop = FleetRouteStop::query()
            ->with(['customer:id,name,phone,address,city', 'route:id,agent_id,route_date,status', 'posSale:id,invoice_number'])
            ->where('user_id', $tenantUserId)
            ->whereKey($stopId)
            ->first();

        if ($stop === null || (int) $stop->route?->agent_id !== (int) $agent->id) {
            throw new InvalidArgumentException('محطة الزيارة غير موجودة.');
        }

        return $stop;
    }

    /**
     * @return Collection<int, object>
     */
    public function custodyBalance(int $tenantUserId, FleetAgent $agent): Collection
    {
        return $this->custodyBalances->linesForAgent($tenantUserId, (int) $agent->id);
    }

    /**
     * @return Collection<int, FleetProduct>
     */
    public function activeProducts(int $tenantUserId): Collection
    {
        return FleetProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sale_price', 'image_url']);
    }

    /**
     * @return Collection<int, FleetCollection>
     */
    public function recentCollections(int $tenantUserId, FleetAgent $agent, int $limit = 20): Collection
    {
        return FleetCollection::query()
            ->with(['customer:id,name,phone'])
            ->withCount('lines')
            ->where('user_id', $tenantUserId)
            ->where('agent_id', $agent->id)
            ->orderByDesc('collected_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function collectionForAgent(int $tenantUserId, FleetAgent $agent, int $collectionId): FleetCollection
    {
        $collection = FleetCollection::query()
            ->with(['customer:id,name,phone', 'lines.product:id,name,sku'])
            ->where('user_id', $tenantUserId)
            ->where('agent_id', $agent->id)
            ->whereKey($collectionId)
            ->first();

        if ($collection === null) {
            throw new InvalidArgumentException('سند التحصيل غير موجود.');
        }

        return $collection;
    }

    /**
     * @return array<string, mixed>
     */
    public function routePayload(FleetRoute $route): array
    {
        return [
            'id' => (int) $route->id,
            'route_date' => $route->route_date->toDateString(),
            'status' => $route->status,
            'notes' => $route->notes,
            'stops_count' => (int) ($route->stops_count ?? $route->stops?->count() ?? 0),
            'started_at' => $route->started_at?->toIso8601String(),
            'completed_at' => $route->completed_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function routeDetailPayload(FleetRoute $route): array
    {
        $payload = $this->routePayload($route);
        $payload['stops'] = $route->stops
            ->sortBy('sort_order')
            ->values()
            ->map(fn (FleetRouteStop $stop): array => $this->stopPayload($stop))
            ->all();

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function stopPayload(FleetRouteStop $stop): array
    {
        return [
            'id' => (int) $stop->id,
            'sort_order' => (int) $stop->sort_order,
            'status' => $stop->status,
            'visited_at' => $stop->visited_at?->toIso8601String(),
            'notes' => $stop->notes,
            'customer' => $stop->customer ? [
                'id' => (int) $stop->customer->id,
                'name' => $stop->customer->name,
                'phone' => $stop->customer->phone,
                'address' => $stop->customer->address,
                'city' => $stop->customer->city,
            ] : null,
            'store_order' => $stop->pos_sale_id ? [
                'id' => (int) $stop->pos_sale_id,
                'invoice_number' => $stop->posSale?->invoice_number,
                'total_amount' => $stop->posSale?->total_amount !== null
                    ? (float) $stop->posSale->total_amount
                    : null,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function collectionPayload(FleetCollection $collection, bool $withLines = false): array
    {
        $payload = [
            'id' => (int) $collection->id,
            'collection_number' => $collection->collection_number,
            'collected_on' => $collection->collected_on?->toDateString(),
            'payment_method' => $collection->payment_method,
            'subtotal' => (float) $collection->subtotal,
            'status' => $collection->status,
            'customer' => $collection->customer ? [
                'id' => (int) $collection->customer->id,
                'name' => $collection->customer->name,
                'phone' => $collection->customer->phone,
            ] : null,
            'lines_count' => (int) ($collection->lines_count ?? $collection->lines?->count() ?? 0),
        ];

        if ($withLines && $collection->relationLoaded('lines')) {
            $payload['lines'] = $collection->lines->map(static fn ($line): array => [
                'product_id' => (int) $line->product_id,
                'product_name' => $line->product?->name,
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'line_total' => round((float) $line->quantity * (float) $line->unit_price, 4),
            ])->values()->all();
        }

        return $payload;
    }
}
