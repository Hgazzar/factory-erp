<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\AuditTrail;
use App\Models\Employee;
use App\Models\User;
use App\Support\AuditModuleCatalog;
use App\Support\ErpRoles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * استعلام سجل التدقيق (audit_trails + audit_logs) مع فلاتر المستأجر.
 */
final class AuditLogViewerService
{
    /**
     * @param  array{
     *     source?: string,
     *     user_id?: int|null,
     *     action?: string|null,
     *     module?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     * }  $filters
     */
    public function paginateTrails(int $viewerUserId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $userIds = $this->scopedUserIds($viewerUserId);
        $query = AuditTrail::query()
            ->with('user:id,name,email')
            ->whereIn('user_id', $userIds)
            ->when(filled($filters['user_id'] ?? null), fn (Builder $q) => $q->where('user_id', (int) $filters['user_id']))
            ->when(filled($filters['action'] ?? null), fn (Builder $q) => $q->where('action', (string) $filters['action']))
            ->when(filled($filters['module'] ?? null), fn (Builder $q) => $q->where('table_name', (string) $filters['module']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{
     *     user_id?: int|null,
     *     action?: string|null,
     *     module?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     * }  $filters
     */
    public function paginateControlLogs(int $viewerUserId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $userIds = $this->scopedUserIds($viewerUserId);
        $systemWide = $this->hasSystemWideAccess();

        $query = AuditLog::query()
            ->with(['actor:id,name,email', 'targetUser:id,name,email'])
            ->when(! $systemWide, function (Builder $q) use ($userIds): void {
                $q->where(function (Builder $sub) use ($userIds): void {
                    $sub->whereIn('actor_id', $userIds)
                        ->orWhereIn('target_user_id', $userIds);
                });
            })
            ->when(filled($filters['user_id'] ?? null), fn (Builder $q) => $q->where('actor_id', (int) $filters['user_id']))
            ->when(filled($filters['action'] ?? null), fn (Builder $q) => $q->where('action', (string) $filters['action']))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('logged_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('logged_at', '<=', $filters['date_to']))
            ->orderByDesc('logged_at')
            ->orderByDesc('id');

        if (filled($filters['module'] ?? null)) {
            $module = (string) $filters['module'];
            $query->where(function (Builder $q) use ($module): void {
                foreach ($this->actionsForControlModule($module) as $action) {
                    $q->orWhere('action', $action);
                }
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return list<int>
     */
    public function scopedUserIds(int $tenantOrViewerUserId): array
    {
        $linked = Employee::withoutGlobalScopes()
            ->where('user_id', $tenantOrViewerUserId)
            ->whereNotNull('linked_user_id')
            ->pluck('linked_user_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->all();

        return array_values(array_unique(array_merge([$tenantOrViewerUserId], $linked)));
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function filterUsers(int $tenantUserId)
    {
        $ids = $this->scopedUserIds($tenantUserId);

        return User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function hasSystemWideAccess(?User $viewer = null): bool
    {
        $viewer ??= auth()->user();
        if ($viewer === null) {
            return false;
        }

        return (int) $viewer->id === 1 || ErpRoles::hasFinanceAdminPanelAccess($viewer);
    }

    /**
     * @return list<string>
     */
    private function actionsForControlModule(string $module): array
    {
        $map = [];
        foreach (array_keys(AuditModuleCatalog::controlActionLabels()) as $action) {
            if (AuditModuleCatalog::controlModuleForAction($action) === $module) {
                $map[] = $action;
            }
        }

        return $map !== [] ? $map : [$module];
    }
}
