<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class FleetRouteService
{
    /**
     * @param  list<int>  $customerIds  ordered stop list
     */
    public function create(int $tenantUserId, int $agentId, string $routeDate, array $customerIds, ?string $notes = null): FleetRoute
    {
        $this->assertAgent($tenantUserId, $agentId);
        $customerIds = $this->normalizeCustomerIds($tenantUserId, $customerIds);

        if ($customerIds === []) {
            throw new InvalidArgumentException('يجب اختيار عميل واحد على الأقل في خط السير.');
        }

        if (FleetRoute::query()
            ->where('user_id', $tenantUserId)
            ->where('agent_id', $agentId)
            ->whereDate('route_date', $routeDate)
            ->exists()) {
            throw new InvalidArgumentException('يوجد خط سير مسجّل لهذا المندوب في نفس اليوم.');
        }

        return DB::transaction(function () use ($tenantUserId, $agentId, $routeDate, $customerIds, $notes): FleetRoute {
            $route = FleetRoute::query()->create([
                'user_id' => $tenantUserId,
                'agent_id' => $agentId,
                'route_date' => $routeDate,
                'status' => FleetRoute::STATUS_PLANNED,
                'notes' => $this->nullable($notes),
            ]);

            $this->syncStops($route, $tenantUserId, $customerIds);

            return $route->fresh(['agent:id,name', 'stops.customer:id,name,phone,city']);
        });
    }

    /**
     * @param  list<int>  $customerIds
     */
    public function update(FleetRoute $route, int $tenantUserId, array $customerIds, ?string $notes = null): FleetRoute
    {
        $this->assertOwned($route, $tenantUserId);

        if (in_array($route->status, [FleetRoute::STATUS_COMPLETED, FleetRoute::STATUS_CANCELLED], true)) {
            throw new InvalidArgumentException('لا يمكن تعديل خط سير مكتمل أو ملغى.');
        }

        $customerIds = $this->normalizeCustomerIds($tenantUserId, $customerIds);

        if ($customerIds === []) {
            throw new InvalidArgumentException('يجب اختيار عميل واحد على الأقل في خط السير.');
        }

        return DB::transaction(function () use ($route, $tenantUserId, $customerIds, $notes): FleetRoute {
            $route->update([
                'notes' => $this->nullable($notes ?? $route->notes),
            ]);

            $this->syncStops($route, $tenantUserId, $customerIds);

            return $route->fresh(['agent:id,name', 'stops.customer:id,name,phone,city']);
        });
    }

    public function start(FleetRoute $route, int $tenantUserId): FleetRoute
    {
        $this->assertOwned($route, $tenantUserId);

        if ($route->status === FleetRoute::STATUS_CANCELLED) {
            throw new InvalidArgumentException('خط السير ملغى.');
        }

        $route->update([
            'status' => FleetRoute::STATUS_IN_PROGRESS,
            'started_at' => $route->started_at ?? now(),
        ]);

        return $route->fresh();
    }

    public function complete(FleetRoute $route, int $tenantUserId): FleetRoute
    {
        $this->assertOwned($route, $tenantUserId);

        $route->update([
            'status' => FleetRoute::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return $route->fresh();
    }

    public function cancel(FleetRoute $route, int $tenantUserId): FleetRoute
    {
        $this->assertOwned($route, $tenantUserId);

        $route->update([
            'status' => FleetRoute::STATUS_CANCELLED,
        ]);

        return $route->fresh();
    }

    public function updateStopStatus(FleetRouteStop $stop, int $tenantUserId, string $status): FleetRouteStop
    {
        if ((int) $stop->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('محطة الزيارة غير تابعة لهذا الحساب.');
        }

        if (! in_array($status, [FleetRouteStop::STATUS_PENDING, FleetRouteStop::STATUS_VISITED, FleetRouteStop::STATUS_SKIPPED], true)) {
            throw new InvalidArgumentException('حالة الزيارة غير صالحة.');
        }

        $stop->update([
            'status' => $status,
            'visited_at' => $status === FleetRouteStop::STATUS_VISITED ? now() : null,
        ]);

        return $stop->fresh(['customer:id,name,phone']);
    }

    /**
     * @param  list<int>  $customerIds
     */
    private function syncStops(FleetRoute $route, int $tenantUserId, array $customerIds): void
    {
        $existing = $route->stops()->get()->keyBy('customer_id');
        $keepIds = [];

        foreach (array_values($customerIds) as $index => $customerId) {
            $stop = $existing->get($customerId);

            if ($stop instanceof FleetRouteStop) {
                $stop->update(['sort_order' => $index + 1]);
                $keepIds[] = (int) $stop->id;

                continue;
            }

            $created = FleetRouteStop::query()->create([
                'user_id' => $tenantUserId,
                'route_id' => $route->id,
                'customer_id' => $customerId,
                'sort_order' => $index + 1,
                'status' => FleetRouteStop::STATUS_PENDING,
            ]);
            $keepIds[] = (int) $created->id;
        }

        $route->stops()->whereNotIn('id', $keepIds)->delete();
    }

    /** @return list<int> */
    private function normalizeCustomerIds(int $tenantUserId, array $customerIds): array
    {
        $ids = [];
        foreach ($customerIds as $id) {
            $id = (int) $id;
            if ($id > 0 && ! in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $valid = FleetCustomer::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCustomer::STATUS_ACTIVE)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ordered = [];
        foreach ($ids as $id) {
            if (in_array($id, $valid, true)) {
                $ordered[] = $id;
            }
        }

        return $ordered;
    }

    private function assertAgent(int $tenantUserId, int $agentId): void
    {
        $exists = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('id', $agentId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->exists();

        if (! $exists) {
            throw new InvalidArgumentException('المندوب غير موجود أو غير نشط.');
        }
    }

    private function assertOwned(FleetRoute $route, int $tenantUserId): void
    {
        if ((int) $route->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('خط السير غير تابع لهذا الحساب.');
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
