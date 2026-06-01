<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Tenant\NicheCatalog;
use App\Services\Tenant\NicheLexiconService;
use App\Services\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحمّل نيش المستأجر وقاموس المسميات بعد المصادقة.
 */
final class ApplyTenantNicheContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly NicheLexiconService $lexiconService,
        private readonly NicheCatalog $nicheCatalog,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        if ($this->tenantContext->isPlatformOperator()) {
            View::share([
                'tenantNicheKey' => null,
                'tenantNiche' => null,
                'nicheLexicon' => [],
            ]);

            return $next($request);
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId();
        $nicheKey = $this->lexiconService->resolveNicheKey($tenantUserId);
        $niche = $nicheKey !== null ? $this->nicheCatalog->find($nicheKey) : null;
        $lexicon = $this->lexiconService->lexiconForTenant($tenantUserId);

        $request->attributes->set('tenant_niche_key', $nicheKey);
        $request->attributes->set('tenant_niche_lexicon', $lexicon);

        View::share([
            'tenantNicheKey' => $nicheKey,
            'tenantNiche' => $niche,
            'nicheLexicon' => $lexicon,
        ]);

        return $next($request);
    }
}
