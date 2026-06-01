<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\User;
use App\Support\ErpRoles;

/**
 * سياق لوحة التحكم الرئيسية: النيش، الموديولات، المزايا البريميوم، والروابط السريعة.
 */
final class TenantDashboardPackageService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantModuleRegistry $moduleRegistry,
        private readonly TenantFeatureRegistry $featureRegistry,
        private readonly NicheLexiconService $lexiconService,
        private readonly NicheCatalog $nicheCatalog,
        private readonly PremiumFeatureCatalog $premiumCatalog,
    ) {}

    /**
     * @return array{
     *     niche_key: ?string,
     *     niche_name: ?string,
     *     modules: list<array{key: string, label: string}>,
     *     premium_features: list<array{key: string, name_ar: string}>,
     *     quick_links: list<array{label: string, route: string, hint: string}>,
     * }
     */
    public function buildForViewer(?User $viewer = null): array
    {
        $viewer ??= auth()->user();
        if (! $viewer instanceof User || ErpRoles::isSuperAdmin($viewer)) {
            return [
                'niche_key' => null,
                'niche_name' => null,
                'modules' => [],
                'premium_features' => [],
                'quick_links' => [],
            ];
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($viewer);
        if ($tenantUserId === null) {
            return [
                'niche_key' => null,
                'niche_name' => null,
                'modules' => [],
                'premium_features' => [],
                'quick_links' => [],
            ];
        }

        $nicheKey = $this->lexiconService->resolveNicheKey($tenantUserId);
        $niche = $nicheKey !== null ? $this->nicheCatalog->find($nicheKey) : null;
        $nicheName = $niche['name_ar'] ?? $niche['name_en'] ?? null;

        $moduleKeys = array_values(array_filter(
            $this->moduleRegistry->enabledKeys($tenantUserId),
            static fn (string $k): bool => $k !== 'core'
        ));

        $modules = [];
        foreach ($moduleKeys as $key) {
            $modules[] = [
                'key' => $key,
                'label' => $this->lexiconService->moduleLabel($key, $tenantUserId),
            ];
        }

        $enabledFeatureKeys = $this->featureRegistry->enabledKeysForTenant($tenantUserId);
        $premiumDefs = $this->premiumCatalog->definitionsForNiche($nicheKey);
        $premiumFeatures = [];
        foreach ($premiumDefs as $def) {
            $key = strtolower((string) ($def['key'] ?? ''));
            if ($key !== '' && in_array($key, $enabledFeatureKeys, true)) {
                $premiumFeatures[] = [
                    'key' => $key,
                    'name_ar' => (string) ($def['name_ar'] ?? $key),
                ];
            }
        }

        return [
            'niche_key' => $nicheKey,
            'niche_name' => $nicheName,
            'modules' => $modules,
            'premium_features' => $premiumFeatures,
            'quick_links' => $this->quickLinksForNiche($nicheKey, $moduleKeys),
        ];
    }

    /**
     * @param  list<string>  $moduleKeys
     * @return list<array{label: string, route: string, hint: string}>
     */
    private function quickLinksForNiche(?string $nicheKey, array $moduleKeys): array
    {
        $has = static fn (string $m): bool => in_array($m, $moduleKeys, true);

        $links = match ($nicheKey) {
            'retail' => array_filter([
                $has('pos') ? ['label' => 'لوحة نقاط البيع', 'route' => 'pos.dashboard', 'hint' => 'مبيعات اليوم والأجهزة'] : null,
                $has('sales') ? ['label' => 'لوحة المبيعات', 'route' => 'sales.dashboard', 'hint' => 'فواتير وتحصيلات'] : null,
                $has('crm') ? ['label' => 'لوحة CRM', 'route' => 'crm.dashboard', 'hint' => 'عملاء وفرص'] : null,
                $has('inventory') ? ['label' => 'تقييم المخزون', 'route' => 'inventory.reports.valuation', 'hint' => 'قيمة المخزون الحالية'] : null,
            ]),
            'manufacturing' => array_filter([
                $has('manufacturing') ? ['label' => 'تسجيل الإنتاج', 'route' => 'operations.production-entry.create', 'hint' => 'إدخال إنتاج لحظي'] : null,
                $has('manufacturing') ? ['label' => 'لوحة التصنيع', 'route' => 'manufacturing.dashboard', 'hint' => 'أوامر تشغيل وBOM'] : null,
                $has('manufacturing') ? ['label' => 'لوحة العمليات', 'route' => 'operations.dashboard.index', 'hint' => 'ورديات وإنجاز'] : null,
                $has('inventory') ? ['label' => 'تقييم المخزون', 'route' => 'inventory.reports.valuation', 'hint' => 'قيمة المخزون'] : null,
            ]),
            'medical_clinics' => array_filter([
                $has('clinic') ? ['label' => 'لوحة العيادة', 'route' => 'clinic.dashboard', 'hint' => 'مواعيد ومرضى'] : null,
                $has('hr') ? ['label' => 'الموارد البشرية', 'route' => 'hr.dashboard', 'hint' => 'موظفون وحضور'] : null,
            ]),
            default => array_filter([
                $has('finance') ? ['label' => 'لوحة المحاسبة', 'route' => 'finance.dashboard', 'hint' => 'مالية'] : null,
                $has('inventory') ? ['label' => 'لوحة المخزون', 'route' => 'inventory.dashboard', 'hint' => 'مخازن'] : null,
            ]),
        };

        $links[] = ['label' => 'سجل التدقيق', 'route' => 'system.audit.index', 'hint' => 'مراقبة التغييرات'];
        if ($has('finance')) {
            $links[] = ['label' => 'أرباح وخسائر', 'route' => 'finance.reports.profit-loss', 'hint' => 'تقرير مالي'];
        }

        return array_values($links);
    }
}
