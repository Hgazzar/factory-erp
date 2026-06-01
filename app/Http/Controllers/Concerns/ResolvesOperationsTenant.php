<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Services\Tenant\TenantContext;
use RuntimeException;

trait ResolvesOperationsTenant
{
    protected function resolveOperationsTenantUserId(): int
    {
        $tenantContext = app(TenantContext::class);
        $tenantUserId = $tenantContext->resolveTenantUserId();

        if ($tenantUserId !== null) {
            return $tenantUserId;
        }

        if ($tenantContext->isPlatformOperator()) {
            return (int) auth()->id();
        }

        throw new RuntimeException('تعذّر تحديد المستأجر لهذه العملية.');
    }
}
