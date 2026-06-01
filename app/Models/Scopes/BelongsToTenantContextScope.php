<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * يقيّد الاستعلامات لسجلات مالك المستأجر (يدعم admin والموظف المرتبط).
 */
class BelongsToTenantContextScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $tenantContext = app(TenantContext::class);
        $tenantUserId = $tenantContext->resolveTenantUserId();

        if ($tenantUserId === null && $tenantContext->isPlatformOperator()) {
            $tenantUserId = (int) Auth::id();
        }

        if ($tenantUserId !== null) {
            $builder->where($model->getTable().'.user_id', $tenantUserId);
        }
    }
}
