<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

use App\Models\Nursery\Subscription;
use Illuminate\Support\Collection;

/**
 * السجل المالي (اشتراكات) لأطفال ولي الأمر — قراءة فقط.
 */
final class NurseryPortalFinanceService
{
    public function __construct(
        private readonly NurseryPortalAccessService $access,
    ) {}

    /**
     * @return Collection<int, Subscription>
     */
    public function subscriptionsForGuardian(int $tenantUserId, int $guardianId): Collection
    {
        $childIds = $this->access->allowedChildIds($tenantUserId, $guardianId);

        if ($childIds === []) {
            return collect();
        }

        return Subscription::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->whereIn('child_id', $childIds)
            ->with(['child:id,name,code', 'plan:id,name'])
            ->orderByDesc('starts_on')
            ->get();
    }
}
