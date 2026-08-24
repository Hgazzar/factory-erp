<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\AuditTrail;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetVisit;
use Illuminate\Support\Collection;

/**
 * استعلامات لوحة المدير: الاستثناءات الجغرافية + المواقع المعلّقة بانتظار الاعتماد.
 * Management by Exception — المدير يرى المشاكل فقط.
 */
final class FleetGeoVerificationService
{
    /**
     * يعتمد المدير الموقع المقترح من المندوب — يُسجَّل في AuditTrail (سجل غير قابل للتعديل).
     */
    public function approveLocation(FleetCustomer $customer): bool
    {
        if ($customer->location_status !== FleetCustomer::LOCATION_PENDING) {
            return false;
        }

        if ($customer->latitude === null || $customer->longitude === null) {
            return false;
        }

        $old = [
            'location_status' => $customer->location_status,
            'location_source' => $customer->location_source,
            'latitude' => $customer->latitude,
            'longitude' => $customer->longitude,
        ];

        $customer->forceFill([
            'location_status' => FleetCustomer::LOCATION_APPROVED,
            'location_source' => FleetCustomer::LOCATION_SOURCE_MANAGER,
            'location_updated_at' => now(),
        ])->save();

        AuditTrail::log(
            'approve_location',
            'fleet_customers',
            $customer->id,
            $old,
            [
                'location_status' => FleetCustomer::LOCATION_APPROVED,
                'location_source' => FleetCustomer::LOCATION_SOURCE_MANAGER,
                'latitude' => $customer->latitude,
                'longitude' => $customer->longitude,
            ],
        );

        return true;
    }

    public function exceptionsCount(int $tenantUserId): int
    {
        return (int) $this->exceptionsQuery($tenantUserId)->count();
    }

    public function pendingLocationsCount(int $tenantUserId): int
    {
        return (int) $this->pendingLocationsQuery($tenantUserId)->count();
    }

    /**
     * @return Collection<int, FleetVisit>
     */
    public function recentExceptions(int $tenantUserId, int $limit = 20): Collection
    {
        return $this->exceptionsQuery($tenantUserId)
            ->with(['agent:id,name', 'customer:id,name,city'])
            ->latest('visited_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, FleetCustomer>
     */
    public function pendingLocations(int $tenantUserId, int $limit = 20): Collection
    {
        return $this->pendingLocationsQuery($tenantUserId)
            ->with('assignedAgent:id,name')
            ->latest('location_updated_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<FleetVisit>
     */
    private function exceptionsQuery(int $tenantUserId)
    {
        return FleetVisit::query()
            ->where('user_id', $tenantUserId)
            ->where(function ($query): void {
                $query->where('is_mocked', true)
                    ->orWhere('geofence_status', FleetVisit::GEOFENCE_OUTSIDE);
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<FleetCustomer>
     */
    private function pendingLocationsQuery(int $tenantUserId)
    {
        return FleetCustomer::query()
            ->where('user_id', $tenantUserId)
            ->where('location_status', FleetCustomer::LOCATION_PENDING);
    }
}
