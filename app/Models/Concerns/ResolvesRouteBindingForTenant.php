<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait ResolvesRouteBindingForTenant
{
    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = static::withoutGlobalScopes()
            ->where($field, $value)
            ->first();

        if (! $model) {
            abort(404);
        }

        if (! auth()->check()) {
            abort(403);
        }

        $tenantContext = app(TenantContext::class);
        $tenantUserId = $tenantContext->resolveTenantUserId();

        if ($tenantUserId === null && $tenantContext->isPlatformOperator()) {
            $tenantUserId = (int) auth()->id();
        }

        if ($tenantUserId === null || (int) $model->user_id !== $tenantUserId) {
            abort(403);
        }

        return $model;
    }
}
