<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetVisit;

/**
 * يحسب المسافة الجغرافية (Haversine) ويحدد حالة الـgeofence مقابل موقع العميل المعتمد.
 * لا يرفض أي زيارة — soft-flag فقط.
 */
final class LocationValidatorService
{
    private const EARTH_RADIUS_METERS = 6_371_000.0;

    public function isValidLatitude(?float $lat): bool
    {
        return $lat !== null && $lat >= -90.0 && $lat <= 90.0;
    }

    public function isValidLongitude(?float $lng): bool
    {
        return $lng !== null && $lng >= -180.0 && $lng <= 180.0;
    }

    public function hasValidCoordinates(?float $lat, ?float $lng): bool
    {
        return $this->isValidLatitude($lat) && $this->isValidLongitude($lng);
    }

    public function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * asin(min(1.0, sqrt($a)));
    }

    public function radiusForCustomer(FleetCustomer $customer): int
    {
        $custom = $customer->geofence_radius;
        if (is_int($custom) && $custom > 0) {
            return $custom;
        }

        return max(1, (int) config('fleet.geofence.default_radius_meters', 150));
    }

    /**
     * يقيّم الزيارة مقابل موقع العميل.
     *
     * @return array{status: string, distance: int|null}
     */
    public function evaluate(FleetCustomer $customer, ?float $lat, ?float $lng): array
    {
        if (! $this->hasValidCoordinates($lat, $lng)) {
            return ['status' => FleetVisit::GEOFENCE_UNVERIFIED, 'distance' => null];
        }

        if (! $customer->hasApprovedLocation()) {
            return ['status' => FleetVisit::GEOFENCE_UNVERIFIED, 'distance' => null];
        }

        $distance = (int) round($this->haversineMeters(
            (float) $customer->latitude,
            (float) $customer->longitude,
            (float) $lat,
            (float) $lng,
        ));

        $status = $distance <= $this->radiusForCustomer($customer)
            ? FleetVisit::GEOFENCE_INSIDE
            : FleetVisit::GEOFENCE_OUTSIDE;

        return ['status' => $status, 'distance' => $distance];
    }
}
