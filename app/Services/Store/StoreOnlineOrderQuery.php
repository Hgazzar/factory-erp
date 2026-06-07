<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\PosSale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Central query builder for online-store orders (list UI + dashboard aggregates).
 */
final class StoreOnlineOrderQuery
{
    public function forTenant(int $tenantUserId): Builder
    {
        return PosSale::withoutGlobalScopes()
            ->forTenant($tenantUserId)
            ->onlineStore();
    }

    public function paginatedList(int $tenantUserId, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->forTenant($tenantUserId)
            ->withOptionalStatus($status)
            ->with(['items.product'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
