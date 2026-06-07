<?php

declare(strict_types=1);

namespace App\Services\Nursery\Portal;

use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use Illuminate\Support\Collection;

/**
 * يتحقق من ملكية ولي الأمر لأطفاله داخل مستأجر واحد — بدون الاعتماد على BelongsToTenantContextScope.
 */
final class NurseryPortalAccessService
{
    /**
     * @return list<int>
     */
    public function allowedChildIds(int $tenantUserId, int $guardianId): array
    {
        return Child::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('guardian_id', $guardianId)
            ->where('status', Child::STATUS_ACTIVE)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, Child>
     */
    public function activeChildrenForGuardian(int $tenantUserId, int $guardianId): Collection
    {
        return Child::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('guardian_id', $guardianId)
            ->where('status', Child::STATUS_ACTIVE)
            ->with(['activeEnrollment.classroom'])
            ->orderBy('name')
            ->get();
    }

    public function assertGuardianOwnsChild(int $tenantUserId, int $guardianId, int $childId): Child
    {
        $child = Child::query()
            ->withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->where('guardian_id', $guardianId)
            ->whereKey($childId)
            ->first();

        if ($child === null) {
            abort(404);
        }

        return $child;
    }
}
