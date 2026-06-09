<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetRouteStop;
use App\Models\PosSale;
use App\Services\Pos\PosSaleFulfillmentService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class FleetStoreOrderService
{
    /**
     * After online checkout: sync fleet customer and mark field-delivery orders for the route pool.
     */
    public function afterOnlineCheckout(int $tenantUserId, PosSale $sale, string $fulfillmentMode): PosSale
    {
        $fulfillmentMode = strtolower(trim($fulfillmentMode));
        if ($fulfillmentMode === '' || $fulfillmentMode === PosSale::FULFILLMENT_PICKUP) {
            return $sale;
        }

        if ($fulfillmentMode !== PosSale::FULFILLMENT_FIELD_DELIVERY) {
            throw new InvalidArgumentException('نوع التسليم غير مدعوم.');
        }

        if ($sale->sale_channel !== PosSale::CHANNEL_ONLINE_STORE) {
            return $sale;
        }

        return DB::transaction(function () use ($tenantUserId, $sale): PosSale {
            $customer = $this->upsertCustomerFromOrder($tenantUserId, $sale);

            $sale->forceFill([
                'fulfillment_mode' => PosSale::FULFILLMENT_FIELD_DELIVERY,
                'fulfillment_status' => PosSale::FULFILLMENT_STATUS_PENDING,
                'fleet_customer_id' => $customer->id,
                'assigned_agent_id' => $customer->assigned_agent_id,
            ])->save();

            return $sale->fresh(['items.product']);
        });
    }

    public function upsertCustomerFromOrder(int $tenantUserId, PosSale $sale): FleetCustomer
    {
        $phone = $this->normalizePhone((string) $sale->customer_phone);
        $name = trim((string) $sale->customer_name);
        $address = trim((string) $sale->customer_address);

        if ($name === '') {
            throw new InvalidArgumentException('اسم العميل مطلوب.');
        }

        $existing = null;
        if ($phone !== '') {
            $existing = FleetCustomer::query()
                ->where('user_id', $tenantUserId)
                ->where('phone', $phone)
                ->first();
        }

        if ($existing !== null) {
            $existing->update([
                'name' => $name,
                'address' => $address !== '' ? $address : $existing->address,
                'status' => FleetCustomer::STATUS_ACTIVE,
            ]);

            return $existing->fresh();
        }

        return FleetCustomer::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'address' => $address !== '' ? $address : null,
            'status' => FleetCustomer::STATUS_ACTIVE,
        ]);
    }

    public function markFulfilled(PosSale $sale): void
    {
        if (! $sale->isFieldDelivery()) {
            return;
        }

        if ($sale->fulfillment_status === PosSale::FULFILLMENT_STATUS_FULFILLED) {
            return;
        }

        $sale->forceFill(['fulfillment_status' => PosSale::FULFILLMENT_STATUS_FULFILLED])->save();
    }

    public function onRouteStopVisited(int $tenantUserId, FleetRouteStop $stop, ?int $actingUserId = null): void
    {
        if ($stop->pos_sale_id === null) {
            return;
        }

        $sale = PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereKey($stop->pos_sale_id)
            ->first();

        if ($sale === null || ! $sale->isFieldDelivery()) {
            return;
        }

        $this->markFulfilled($sale);

        if ($sale->fresh()->isOnlineCodPending()) {
            app(PosSaleFulfillmentService::class)->markDelivered($tenantUserId, (int) $sale->id, $actingUserId);
        }
    }

    public function markOutForDeliveryOnRoute(int $tenantUserId, int $routeId): void
    {
        $saleIds = FleetRouteStop::query()
            ->where('user_id', $tenantUserId)
            ->where('route_id', $routeId)
            ->whereNotNull('pos_sale_id')
            ->pluck('pos_sale_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($saleIds === []) {
            return;
        }

        PosSale::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereIn('id', $saleIds)
            ->where('fulfillment_mode', PosSale::FULFILLMENT_FIELD_DELIVERY)
            ->where('fulfillment_status', PosSale::FULFILLMENT_STATUS_ASSIGNED)
            ->update(['fulfillment_status' => PosSale::FULFILLMENT_STATUS_OUT_FOR_DELIVERY]);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return trim($digits);
    }
}
