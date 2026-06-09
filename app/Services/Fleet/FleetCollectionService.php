<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetCollectionLine;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetProduct;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class FleetCollectionService
{
    public function __construct(
        private readonly FleetCustodyBalanceService $balances,
        private readonly FleetStoreOrderService $storeOrders,
    ) {}

    /**
     * @param  list<array{product_id: int, quantity: float, unit_price?: float|null}>  $lines
     * @param  array{
     *   customer_id?: int|null,
     *   route_id?: int|null,
     *   route_stop_id?: int|null,
     *   payment_method?: string|null
     * }  $context
     */
    public function createDraft(
        int $tenantUserId,
        int $agentId,
        string $collectedOn,
        array $lines,
        array $context = [],
        ?string $notes = null,
        ?int $recordedBy = null,
    ): FleetCollection {
        $this->assertAgent($tenantUserId, $agentId);
        $this->assertContext($tenantUserId, $agentId, $context);

        $paymentMethod = $this->normalizePaymentMethod($context['payment_method'] ?? FleetCollection::PAYMENT_COD);
        $normalizedLines = $this->normalizeLines($tenantUserId, $agentId, $lines, validateBalance: false);
        $subtotal = round(array_sum(array_column($normalizedLines, 'line_total')), 4);

        return DB::transaction(function () use (
            $tenantUserId,
            $agentId,
            $collectedOn,
            $normalizedLines,
            $context,
            $paymentMethod,
            $subtotal,
            $notes,
            $recordedBy,
        ): FleetCollection {
            $collection = FleetCollection::query()->create([
                'user_id' => $tenantUserId,
                'agent_id' => $agentId,
                'customer_id' => $context['customer_id'] ?? null,
                'route_id' => $context['route_id'] ?? null,
                'route_stop_id' => $context['route_stop_id'] ?? null,
                'collection_number' => $this->nextCollectionNumber($tenantUserId),
                'collected_on' => $collectedOn,
                'payment_method' => $paymentMethod,
                'subtotal' => $subtotal,
                'status' => FleetCollection::STATUS_DRAFT,
                'notes' => $this->nullable($notes),
                'recorded_by' => $recordedBy,
            ]);

            $this->syncLines($collection, $tenantUserId, $normalizedLines);

            return $collection->fresh([
                'agent:id,name',
                'customer:id,name,phone',
                'route:id,route_date',
                'routeStop.customer:id,name',
                'lines.product:id,name,sku',
            ]);
        });
    }

    public function confirm(FleetCollection $collection, int $tenantUserId): FleetCollection
    {
        $this->assertOwned($collection, $tenantUserId);

        if ($collection->status !== FleetCollection::STATUS_DRAFT) {
            throw new InvalidArgumentException('يمكن تأكيد مسودة التحصيل فقط.');
        }

        $collection->load('lines');
        if ($collection->lines->isEmpty()) {
            throw new InvalidArgumentException('أضف صنفاً واحداً على الأقل قبل تأكيد التحصيل.');
        }

        foreach ($collection->lines as $line) {
            $available = $this->balances->availableQuantity($tenantUserId, (int) $collection->agent_id, (int) $line->product_id);
            if ((float) $line->quantity > $available + 0.0001) {
                $productName = $line->product?->name ?? 'الصنف';
                throw new InvalidArgumentException("الكمية المباعة لـ «{$productName}» تتجاوز رصيد العهدة ({$available}).");
            }
        }

        return DB::transaction(function () use ($collection, $tenantUserId): FleetCollection {
            $collection->update([
                'status' => FleetCollection::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ]);

            if ($collection->route_stop_id !== null) {
                $stop = FleetRouteStop::query()
                    ->where('user_id', $tenantUserId)
                    ->where('id', $collection->route_stop_id)
                    ->first();

                if ($stop !== null && $stop->status === FleetRouteStop::STATUS_PENDING) {
                    $stop->update([
                        'status' => FleetRouteStop::STATUS_VISITED,
                        'visited_at' => now(),
                    ]);
                    $this->storeOrders->onRouteStopVisited($tenantUserId, $stop);
                }
            }

            return $collection->fresh([
                'agent:id,name',
                'customer:id,name,phone',
                'route:id,route_date',
                'routeStop.customer:id,name',
                'lines.product:id,name,sku',
            ]);
        });
    }

    public function void(FleetCollection $collection, int $tenantUserId): FleetCollection
    {
        $this->assertOwned($collection, $tenantUserId);

        if ($collection->status === FleetCollection::STATUS_VOID) {
            throw new InvalidArgumentException('سند التحصيل ملغى بالفعل.');
        }

        $collection->update(['status' => FleetCollection::STATUS_VOID]);

        return $collection->fresh();
    }

    private function nextCollectionNumber(int $tenantUserId): string
    {
        $last = FleetCollection::query()
            ->where('user_id', $tenantUserId)
            ->orderByDesc('id')
            ->value('collection_number');

        $seq = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('FL-COL-%05d', $seq);
    }

    /**
     * @param  list<array{product_id: int, quantity: float, unit_price: float, line_total: float}>  $lines
     */
    private function syncLines(FleetCollection $collection, int $tenantUserId, array $lines): void
    {
        $collection->lines()->delete();

        foreach ($lines as $line) {
            FleetCollectionLine::query()->create([
                'user_id' => $tenantUserId,
                'collection_id' => $collection->id,
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'line_total' => $line['line_total'],
            ]);
        }
    }

    /**
     * @param  list<array{product_id?: mixed, quantity?: mixed, unit_price?: mixed}>  $lines
     * @return list<array{product_id: int, quantity: float, unit_price: float, line_total: float}>
     */
    private function normalizeLines(int $tenantUserId, int $agentId, array $lines, bool $validateBalance): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = round((float) ($line['quantity'] ?? 0), 4);

            if ($productId < 1 || $qty <= 0) {
                continue;
            }

            $product = FleetProduct::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $productId)
                ->where('is_active', true)
                ->first(['id', 'name', 'sale_price']);

            if ($product === null) {
                throw new InvalidArgumentException('أحد الأصناف غير صالح أو غير نشط.');
            }

            $unitPrice = round((float) ($line['unit_price'] ?? $product->sale_price), 4);
            if ($unitPrice < 0) {
                throw new InvalidArgumentException('سعر الوحدة غير صالح.');
            }

            if ($validateBalance) {
                $available = $this->balances->availableQuantity($tenantUserId, $agentId, $productId);
                if ($qty > $available + 0.0001) {
                    throw new InvalidArgumentException("الكمية المباعة لـ «{$product->name}» تتجاوز رصيد العهدة ({$available}).");
                }
            }

            $normalized[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => round($qty * $unitPrice, 4),
            ];
        }

        if ($normalized === []) {
            throw new InvalidArgumentException('أضف صنفاً واحداً على الأقل بكمية أكبر من صفر.');
        }

        return $normalized;
    }

    /**
     * @param  array{
     *   customer_id?: int|null,
     *   route_id?: int|null,
     *   route_stop_id?: int|null,
     *   payment_method?: string|null
     * }  $context
     */
    private function assertContext(int $tenantUserId, int $agentId, array $context): void
    {
        $customerId = isset($context['customer_id']) ? (int) $context['customer_id'] : 0;
        if ($customerId > 0) {
            $exists = FleetCustomer::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $customerId)
                ->where('status', FleetCustomer::STATUS_ACTIVE)
                ->exists();

            if (! $exists) {
                throw new InvalidArgumentException('العميل غير موجود أو غير نشط.');
            }
        }

        $routeId = isset($context['route_id']) ? (int) $context['route_id'] : 0;
        $routeStopId = isset($context['route_stop_id']) ? (int) $context['route_stop_id'] : 0;

        if ($routeStopId > 0) {
            $stop = FleetRouteStop::query()
                ->with('route:id,agent_id,user_id')
                ->where('user_id', $tenantUserId)
                ->where('id', $routeStopId)
                ->first();

            if ($stop === null) {
                throw new InvalidArgumentException('محطة خط السير غير موجودة.');
            }

            if ((int) $stop->route?->agent_id !== $agentId) {
                throw new InvalidArgumentException('محطة خط السير لا تتبع هذا المندوب.');
            }

            if ($routeId > 0 && (int) $stop->route_id !== $routeId) {
                throw new InvalidArgumentException('محطة خط السير لا تتبع خط السير المحدد.');
            }
        } elseif ($routeId > 0) {
            $route = FleetRoute::query()
                ->where('user_id', $tenantUserId)
                ->where('id', $routeId)
                ->first(['id', 'agent_id']);

            if ($route === null) {
                throw new InvalidArgumentException('خط السير غير موجود.');
            }

            if ((int) $route->agent_id !== $agentId) {
                throw new InvalidArgumentException('خط السير لا يتبع هذا المندوب.');
            }
        }
    }

    private function normalizePaymentMethod(mixed $method): string
    {
        $method = strtolower(trim((string) $method));
        $allowed = [
            FleetCollection::PAYMENT_COD,
            FleetCollection::PAYMENT_TRANSFER,
            FleetCollection::PAYMENT_CREDIT,
        ];

        if (! in_array($method, $allowed, true)) {
            throw new InvalidArgumentException('طريقة التحصيل غير صالحة.');
        }

        return $method;
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

    private function assertOwned(FleetCollection $collection, int $tenantUserId): void
    {
        if ((int) $collection->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('سند التحصيل غير تابع لهذا الحساب.');
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
