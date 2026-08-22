<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Data\Navigation\DashboardAction;
use App\Data\Navigation\NavigationLink;
use App\Models\User;
use App\Services\Store\StoreNicheCapabilities;
use App\Support\ClinicAccess;
use App\Support\ErpRoles;
use App\Support\FleetAccess;
use App\Support\NurseryAccess;
use Illuminate\Support\Facades\Route;

/**
 * يحسب روابط التنقل المرئية حسب niche profile + modules + features + capabilities.
 */
final class TenantNavigationService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantModuleRegistry $modules,
        private readonly TenantFeatureRegistry $features,
        private readonly NicheLexiconService $lexiconService,
        private readonly FleetAccess $fleetAccess,
        private readonly ClinicAccess $clinicAccess,
        private readonly NurseryAccess $nurseryAccess,
        private readonly StoreNicheCapabilities $storeNicheCapabilities,
    ) {}

    public function primaryShell(?int $tenantUserId = null): string
    {
        $profile = $this->profileForTenant($tenantUserId);

        return (string) ($profile['primary_shell'] ?? 'erp');
    }

    /**
     * مسار الدخول الافتراضي بعد Login / الرئيسية — حسب الـniche والـmodules المفعّلة.
     * لا يغيّر سلوك النيشات غير Nursery إلا إذا أُضيفت shells لاحقاً.
     */
    public function defaultHomeRoute(?User $user = null): string
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return route('dashboard', absolute: false);
        }

        if ($user->role === 'worker') {
            return Route::has('operations.production-entry.create')
                ? route('operations.production-entry.create', absolute: false)
                : route('dashboard', absolute: false);
        }

        $tenantUserId = $this->tenantContext->resolveTenantUserId($user);

        if ($tenantUserId !== null
            && $this->primaryShell($tenantUserId) === 'nursery'
            && $this->modules->isEnabled('nursery', $tenantUserId)
            && Route::has('nursery.dashboard')
        ) {
            return route('nursery.dashboard', absolute: false);
        }

        return route('dashboard', absolute: false);
    }

    public function isNurseryPrimaryShell(?int $tenantUserId = null): bool
    {
        return $this->primaryShell($tenantUserId) === 'nursery';
    }

    public function detectActiveErpModule(): ?string
    {
        if (request()->is('sales*') || request()->is('reports/statement*')) {
            return 'sales';
        }

        if (request()->is('purchases*')) {
            return 'purchases';
        }

        if (request()->is('inventory*') || request()->is('items*') || request()->is('warehouses*')) {
            return 'inventory';
        }

        if (request()->is('manufacturing*')) {
            return 'manufacturing';
        }

        if (request()->is('finance*')) {
            return 'finance';
        }

        if (request()->is('services*')) {
            return 'services';
        }

        if (request()->is('hr*')) {
            return 'hr';
        }

        return null;
    }

    /**
     * @return array{title: string, iconBg: string, iconColor: string, icon: string}|null
     */
    public function erpModuleShellMeta(string $module): ?array
    {
        $module = strtolower(trim($module));
        $shells = config('navigation.module_shells', []);
        $meta = is_array($shells) ? ($shells[$module] ?? null) : null;

        if (! is_array($meta) || ! isset($meta['title'])) {
            return null;
        }

        return [
            'title' => (string) $meta['title'],
            'iconBg' => (string) ($meta['iconBg'] ?? 'rgba(99, 102, 241, 0.15)'),
            'iconColor' => (string) ($meta['iconColor'] ?? '#4f46e5'),
            'icon' => (string) ($meta['icon'] ?? ''),
        ];
    }

    public function hasVisibleErpModuleSidebar(string $module, ?int $tenantUserId = null): bool
    {
        return $this->visibleLinks($tenantUserId, $module) !== [];
    }

    public function usesUnfilteredNavigation(?int $tenantUserId = null): bool
    {
        $profile = $this->profileForTenant($tenantUserId);

        return (bool) ($profile['expand_all_modules'] ?? false)
            || in_array('*', $profile['surfaces'] ?? [], true);
    }

    public function isDashboardModuleVisible(string $module, ?int $tenantUserId = null): bool
    {
        $module = strtolower(trim($module));
        if ($module === '') {
            return false;
        }

        if ($this->tenantContext->isPlatformOperator()) {
            return $this->modules->isEnabled($module, $tenantUserId);
        }

        foreach ($this->visibleModuleLaunchers($tenantUserId) as $launcher) {
            if (strtolower((string) ($launcher['module'] ?? '')) === $module) {
                return true;
            }
        }

        foreach ($this->visibleLinks($tenantUserId) as $link) {
            if ($link->module === $module) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function visibleDashboardModuleKeys(?int $tenantUserId = null): array
    {
        $keys = [];

        foreach ($this->visibleModuleLaunchers($tenantUserId) as $launcher) {
            $module = strtolower((string) ($launcher['module'] ?? ''));
            if ($module !== '' && ! in_array($module, $keys, true)) {
                $keys[] = $module;
            }
        }

        return $keys;
    }

    public function isDashboardReportsWidgetVisible(?int $tenantUserId = null): bool
    {
        if ($this->tenantContext->isPlatformOperator()) {
            return true;
        }

        return $this->isLinkVisible('sales.reports.statement', $tenantUserId)
            || $this->isLinkVisible('finance.reports.profit_loss', $tenantUserId);
    }

    public function isLinkVisible(string $linkKey, ?int $tenantUserId = null): bool
    {
        return $this->resolveVisibleLink($linkKey, $tenantUserId) !== null;
    }

    public function isDashboardQuickLinkVisible(string $linkKey, ?int $tenantUserId = null): bool
    {
        if ($this->usesUnfilteredNavigation($tenantUserId)) {
            return true;
        }

        $registry = config('navigation.links', []);
        if (! is_array($registry) || ! array_key_exists($linkKey, $registry)) {
            return true;
        }

        return $this->isLinkVisible($linkKey, $tenantUserId);
    }

    /**
     * @return list<DashboardAction>
     */
    public function visibleDashboardActions(string $module, ?int $tenantUserId = null): array
    {
        $module = strtolower(trim($module));
        if ($module === '') {
            return [];
        }

        $configured = config("navigation.dashboard_actions.{$module}", []);
        if (! is_array($configured)) {
            return [];
        }

        $linkRegistry = config('navigation.links', []);
        $actions = [];

        foreach ($configured as $linkKey) {
            if (! is_string($linkKey) || $linkKey === '') {
                continue;
            }

            if (! $this->isDashboardQuickLinkVisible($linkKey, $tenantUserId)) {
                continue;
            }

            $link = $this->resolveVisibleLink($linkKey, $tenantUserId);
            if ($link === null) {
                continue;
            }

            $definition = is_array($linkRegistry[$linkKey] ?? null) ? $linkRegistry[$linkKey] : [];

            $actions[] = new DashboardAction(
                key: $linkKey,
                label: (string) ($definition['dashboard_label'] ?? $link->label),
                route: $link->route,
                icon: isset($definition['dashboard_icon']) ? (string) $definition['dashboard_icon'] : null,
            );
        }

        return $actions;
    }

    /**
     * @return list<NavigationLink>
     */
    public function visibleLinks(?int $tenantUserId = null, ?string $shell = null): array
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();
        $allowedKeys = $this->allowedLinkKeysForTenant($tenantUserId);
        $links = [];

        foreach ($allowedKeys as $linkKey) {
            $link = $this->resolveVisibleLink($linkKey, $tenantUserId);
            if ($link === null) {
                continue;
            }

            if ($shell !== null && $link->shell !== $shell) {
                continue;
            }

            $links[] = $link;
        }

        return $links;
    }

    /**
     * @return list<array{module: string, label: string, subtitle: string, route: string}>
     */
    public function visibleModuleLaunchers(?int $tenantUserId = null): array
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();
        $profile = $this->profileForTenant($tenantUserId);
        $expandAll = (bool) ($profile['expand_all_modules'] ?? false);
        $visibleModules = $this->visibleModuleKeysFromLinks($tenantUserId);
        $launchers = [];

        foreach (config('navigation.launchers', []) as $launcherKey => $launcher) {
            if (! is_array($launcher)) {
                continue;
            }

            $module = strtolower((string) ($launcher['module'] ?? ''));
            if ($module === '') {
                continue;
            }

            if (! $expandAll && ! in_array($module, $visibleModules, true)) {
                continue;
            }

            if (! $this->modules->isEnabled($module, $tenantUserId)) {
                continue;
            }

            if (! $this->passesFleetOperationsGate($launcher, $tenantUserId)) {
                continue;
            }

            $route = (string) ($launcher['route'] ?? '');
            if ($route === '' || ! Route::has($route)) {
                continue;
            }

            $launchers[] = [
                'module' => $module,
                'label' => $this->lexiconService->moduleLabel($module, $tenantUserId),
                'subtitle' => (string) ($launcher['subtitle'] ?? ''),
                'route' => $route,
            ];
        }

        return $launchers;
    }

    /**
     * بطاقات مودال الوصول السريع في erp-global-navbar (مع أيقونات من config).
     *
     * @return list<array{module: string, label: string, subtitle: string, route: string, iconBg: string, iconColor: string, icon: string}>
     */
    public function visibleModuleLauncherCards(?int $tenantUserId = null): array
    {
        if ($this->tenantContext->isPlatformOperator()) {
            return $this->allLauncherCards();
        }

        $cards = [];

        foreach ($this->visibleModuleLaunchers($tenantUserId) as $launcher) {
            $card = $this->hydrateLauncherCard($launcher);
            if ($card !== null) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * @return list<array{label: string, route: string, hint: string}>
     */
    public function quickLinks(?int $tenantUserId = null): array
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();
        $profile = $this->profileForTenant($tenantUserId);
        $quickKeys = $profile['quick_link_keys'] ?? [];
        $links = [];

        foreach ($quickKeys as $linkKey) {
            if (! is_string($linkKey) || $linkKey === '') {
                continue;
            }

            $link = $this->resolveVisibleLink($linkKey, $tenantUserId);
            if ($link === null) {
                continue;
            }

            $links[] = [
                'label' => $link->label,
                'route' => $link->route,
                'hint' => (string) ($link->hint ?? $link->label),
            ];
        }

        $audit = $this->resolveVisibleLink('system.audit', $tenantUserId);
        if ($audit !== null && ! $this->quickLinksContainRoute($links, $audit->route)) {
            $links[] = [
                'label' => $audit->label,
                'route' => $audit->route,
                'hint' => (string) ($audit->hint ?? $audit->label),
            ];
        }

        $profitLoss = $this->resolveVisibleLink('finance.reports.profit_loss', $tenantUserId);
        if ($profitLoss !== null && ! $this->quickLinksContainRoute($links, $profitLoss->route)) {
            $links[] = [
                'label' => $profitLoss->label,
                'route' => $profitLoss->route,
                'hint' => (string) ($profitLoss->hint ?? $profitLoss->label),
            ];
        }

        return $links;
    }

    /**
     * @return list<string>
     */
    private function allowedLinkKeysForTenant(?int $tenantUserId): array
    {
        $profile = $this->profileForTenant($tenantUserId);
        $surfaceKeys = $this->resolveSurfaceKeys($profile);
        $linkKeys = [];

        $surfaceRegistry = config('navigation.surfaces', []);

        foreach ($surfaceKeys as $surfaceKey) {
            $keys = is_array($surfaceRegistry) ? ($surfaceRegistry[$surfaceKey] ?? []) : [];
            if (! is_array($keys)) {
                continue;
            }

            foreach ($keys as $key) {
                if (is_string($key) && $key !== '' && ! in_array($key, $linkKeys, true)) {
                    $linkKeys[] = $key;
                }
            }
        }

        return $linkKeys;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<string>
     */
    private function resolveSurfaceKeys(array $profile): array
    {
        $surfaces = $profile['surfaces'] ?? [];

        if (! is_array($surfaces)) {
            return [];
        }

        if (in_array('*', $surfaces, true)) {
            return array_keys(config('navigation.surfaces', []));
        }

        return array_values(array_filter($surfaces, static fn (mixed $s): bool => is_string($s) && $s !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function profileForTenant(?int $tenantUserId): array
    {
        $tenantUserId ??= $this->tenantContext->resolveTenantUserId();
        $nicheKey = $this->lexiconService->resolveNicheKey($tenantUserId);
        $profiles = config('navigation.profiles', []);

        if ($nicheKey !== null && isset($profiles[$nicheKey]) && is_array($profiles[$nicheKey])) {
            return $profiles[$nicheKey];
        }

        return [
            'primary_shell' => 'erp',
            'surfaces' => ['finance.lite', 'system.core'],
            'quick_link_keys' => ['finance.dashboard'],
        ];
    }

    private function resolveVisibleLink(string $linkKey, ?int $tenantUserId): ?NavigationLink
    {
        $linkKey = trim($linkKey);
        if ($linkKey === '') {
            return null;
        }

        if (! in_array($linkKey, $this->allowedLinkKeysForTenant($tenantUserId), true)) {
            return null;
        }

        $linkRegistry = config('navigation.links', []);
        $definition = is_array($linkRegistry) ? ($linkRegistry[$linkKey] ?? null) : null;
        if (! is_array($definition)) {
            return null;
        }

        $route = (string) ($definition['route'] ?? '');
        if ($route === '' || ! Route::has($route)) {
            return null;
        }

        $module = isset($definition['module']) ? strtolower((string) $definition['module']) : null;
        if ($module !== null && $module !== '' && ! $this->modules->isEnabled($module, $tenantUserId)) {
            return null;
        }

        $feature = isset($definition['feature']) ? strtolower(trim((string) $definition['feature'])) : '';
        if ($feature !== '' && ! $this->features->isEnabled($feature, $tenantUserId)) {
            return null;
        }

        if (! $this->passesFleetOperationsGate($definition, $tenantUserId)) {
            return null;
        }

        $fleetCapability = $definition['fleet_capability'] ?? null;
        if (is_string($fleetCapability) && $fleetCapability !== '' && ! $this->fleetAccess->allows($fleetCapability)) {
            return null;
        }

        $clinicCapability = $definition['clinic_capability'] ?? null;
        if (is_string($clinicCapability) && $clinicCapability !== '' && ! $this->clinicAccess->allows($clinicCapability)) {
            return null;
        }

        $nurseryCapability = $definition['nursery_capability'] ?? null;
        if (is_string($nurseryCapability) && $nurseryCapability !== '' && ! $this->nurseryAccess->allows($nurseryCapability)) {
            return null;
        }

        if (! empty($definition['admin_only']) && ! $this->viewerIsAdminOrSuperAdmin()) {
            return null;
        }

        $active = $definition['active'] ?? $route;
        $activePattern = is_array($active)
            ? array_values(array_map('strval', $active))
            : (string) $active;

        return new NavigationLink(
            key: $linkKey,
            route: $route,
            label: $this->resolveLinkLabel($linkKey, $definition, $tenantUserId),
            shell: (string) ($definition['shell'] ?? 'erp'),
            module: $module !== '' ? $module : null,
            activePattern: is_string($activePattern) && $activePattern === '' ? null : $activePattern,
            group: isset($definition['group']) ? (string) $definition['group'] : null,
            hint: isset($definition['hint']) ? (string) $definition['hint'] : null,
            infoField: isset($definition['info_field']) ? (string) $definition['info_field'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function passesFleetOperationsGate(array $definition, ?int $tenantUserId): bool
    {
        if (empty($definition['requires_fleet_operations'])) {
            return true;
        }

        return $this->fleetAccess->operationsEnabled($tenantUserId);
    }

    /**
     * @return list<string>
     */
    private function visibleModuleKeysFromLinks(?int $tenantUserId): array
    {
        $moduleKeys = [];

        foreach ($this->visibleLinks($tenantUserId) as $link) {
            if ($link->module !== null && ! in_array($link->module, $moduleKeys, true)) {
                $moduleKeys[] = $link->module;
            }
        }

        return $moduleKeys;
    }

    private function viewerIsAdminOrSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && ($user->role === 'admin' || ErpRoles::isSuperAdmin($user));
    }

    private function viewerIsTechnicianOrAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->is_technician || $user->isAdminOrSuperAdmin());
    }

    /**
     * @return list<array{module: string, label: string, subtitle: string, route: string, iconBg: string, iconColor: string, icon: string}>
     */
    private function allLauncherCards(): array
    {
        $cards = [];

        foreach (config('navigation.launchers', []) as $launcher) {
            if (! is_array($launcher)) {
                continue;
            }

            $module = strtolower((string) ($launcher['module'] ?? ''));
            $route = (string) ($launcher['route'] ?? '');

            if ($module === '' || $route === '' || ! Route::has($route)) {
                continue;
            }

            $cards[] = [
                'module' => $module,
                'label' => $this->lexiconService->moduleLabel($module, null),
                'subtitle' => (string) ($launcher['subtitle'] ?? ''),
                'route' => $route,
                'iconBg' => (string) ($launcher['iconBg'] ?? 'rgba(99, 102, 241, 0.15)'),
                'iconColor' => (string) ($launcher['iconColor'] ?? '#4f46e5'),
                'icon' => (string) ($launcher['icon'] ?? ''),
            ];
        }

        return $cards;
    }

    /**
     * @param  array{module: string, label: string, subtitle: string, route: string}  $launcher
     * @return array{module: string, label: string, subtitle: string, route: string, iconBg: string, iconColor: string, icon: string}|null
     */
    private function hydrateLauncherCard(array $launcher): ?array
    {
        $meta = $this->launcherMetaForModule($launcher['module']);

        if ($meta === null) {
            return null;
        }

        return [
            'module' => $launcher['module'],
            'label' => $launcher['label'],
            'subtitle' => $launcher['subtitle'],
            'route' => $launcher['route'],
            'iconBg' => (string) ($meta['iconBg'] ?? 'rgba(99, 102, 241, 0.15)'),
            'iconColor' => (string) ($meta['iconColor'] ?? '#4f46e5'),
            'icon' => (string) ($meta['icon'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function launcherMetaForModule(string $module): ?array
    {
        $module = strtolower(trim($module));

        foreach (config('navigation.launchers', []) as $launcher) {
            if (! is_array($launcher)) {
                continue;
            }

            if (strtolower((string) ($launcher['module'] ?? '')) === $module) {
                return $launcher;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function resolveLinkLabel(string $linkKey, array $definition, ?int $tenantUserId): string
    {
        $nicheLabelKey = $definition['niche_label'] ?? null;
        if (is_string($nicheLabelKey) && $nicheLabelKey !== '') {
            return niche_label($nicheLabelKey, (string) ($definition['label'] ?? $linkKey));
        }

        $storeLabel = $definition['store_label'] ?? null;

        if (! is_string($storeLabel) || $storeLabel === '') {
            return (string) ($definition['label'] ?? $linkKey);
        }

        $nicheKey = $this->lexiconService->resolveNicheKey($tenantUserId);

        return match ($storeLabel) {
            'settings' => $this->storeNicheCapabilities->settingsNavLabel($nicheKey),
            'orders' => $this->storeNicheCapabilities->ordersNavLabel($nicheKey),
            'storefront' => $this->storeNicheCapabilities->storefrontLabel($nicheKey),
            default => (string) ($definition['label'] ?? $linkKey),
        };
    }

    /**
     * @param  list<array{label: string, route: string, hint: string}>  $links
     */
    private function quickLinksContainRoute(array $links, string $route): bool
    {
        foreach ($links as $link) {
            if (($link['route'] ?? '') === $route) {
                return true;
            }
        }

        return false;
    }
}
