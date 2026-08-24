<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Data\Fleet\GeoCapture;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetRouteStop;
use App\Models\Fleet\FleetVisit;

/**
 * يسجّل كل حدث زيارة (بيع/عدم بيع/تخطٍّ) كسجل جغرافي في fleet_visits.
 * يطبّق مبدأ "soft-flag": لا يرفض أي عملية، فقط يوثّق الحالة للمراجعة.
 */
final class FleetVisitService
{
    public function __construct(
        private readonly LocationValidatorService $validator,
    ) {}

    public function recordForStop(
        int $tenantUserId,
        int $agentId,
        FleetRouteStop $stop,
        string $outcome,
        ?string $reason,
        GeoCapture $geo,
    ): FleetVisit {
        return $this->persist(
            tenantUserId: $tenantUserId,
            agentId: $agentId,
            customerId: (int) $stop->customer_id,
            routeStopId: (int) $stop->id,
            collectionId: null,
            outcome: $outcome,
            reason: $reason,
            geo: $geo,
        );
    }

    public function recordForCollection(
        int $tenantUserId,
        int $agentId,
        FleetCollection $collection,
        GeoCapture $geo,
    ): FleetVisit {
        return $this->persist(
            tenantUserId: $tenantUserId,
            agentId: $agentId,
            customerId: $collection->customer_id !== null ? (int) $collection->customer_id : null,
            routeStopId: $collection->route_stop_id !== null ? (int) $collection->route_stop_id : null,
            collectionId: (int) $collection->id,
            outcome: FleetVisit::OUTCOME_SALE,
            reason: null,
            geo: $geo,
        );
    }

    private function persist(
        int $tenantUserId,
        int $agentId,
        ?int $customerId,
        ?int $routeStopId,
        ?int $collectionId,
        string $outcome,
        ?string $reason,
        GeoCapture $geo,
    ): FleetVisit {
        $customer = $customerId !== null
            ? FleetCustomer::query()
                ->where('user_id', $tenantUserId)
                ->find($customerId)
            : null;

        $status = FleetVisit::GEOFENCE_UNVERIFIED;
        $distance = null;

        if ($customer instanceof FleetCustomer) {
            $evaluation = $this->validator->evaluate($customer, $geo->lat, $geo->lng);
            $status = $evaluation['status'];
            $distance = $evaluation['distance'];

            $this->captureColdStartLocation($customer, $geo);
        }

        return FleetVisit::query()->create([
            'user_id' => $tenantUserId,
            'agent_id' => $agentId,
            'customer_id' => $customerId,
            'route_stop_id' => $routeStopId,
            'collection_id' => $collectionId,
            'captured_lat' => $geo->lat,
            'captured_lng' => $geo->lng,
            'accuracy_meters' => $geo->accuracy,
            'is_mocked' => $geo->isMocked,
            'geofence_status' => $status,
            'distance_meters' => $distance,
            'outcome' => $outcome,
            'visit_reason' => $this->nullable($reason),
            'visited_at' => now(),
        ]);
    }

    /**
     * عميل بلا موقع: نلتقط أول اقتراح من المندوب كـ pending بانتظار اعتماد المدير.
     * لا نستبدل موقعاً موجوداً (pending/approved) لتفادي تلاعب المندوب.
     */
    private function captureColdStartLocation(FleetCustomer $customer, GeoCapture $geo): void
    {
        if ($customer->location_status !== FleetCustomer::LOCATION_NONE) {
            return;
        }

        if (! $this->validator->hasValidCoordinates($geo->lat, $geo->lng)) {
            return;
        }

        $customer->forceFill([
            'latitude' => $geo->lat,
            'longitude' => $geo->lng,
            'location_status' => FleetCustomer::LOCATION_PENDING,
            'location_source' => FleetCustomer::LOCATION_SOURCE_AGENT,
            'location_updated_at' => now(),
        ])->save();
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
