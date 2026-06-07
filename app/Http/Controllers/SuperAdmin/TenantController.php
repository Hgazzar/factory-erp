<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SuperAdmin\SuperAdminTenantService;
use App\Services\SuperAdmin\TenantProvisionerService;
use App\Services\Tenant\PremiumFeatureCatalog;
use App\Support\TenantSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class TenantController extends Controller
{
    public function index(Request $request, SuperAdminTenantService $tenants): View
    {
        $paginator = $tenants->paginateTenants($request->integer('per_page') ?: 20);

        $rows = $paginator->getCollection()->map(function (User $tenant) use ($tenants) {
            $summary = $tenants->tenantSummary($tenant);

            return array_merge($summary, [
                'employees_count' => $summary['employee_count'],
            ]);
        });

        $paginator->setCollection($rows);

        return view('super-admin.tenants.index', [
            'tenants' => $paginator,
        ]);
    }

    public function create(SuperAdminTenantService $tenants, PremiumFeatureCatalog $premiumCatalog): View
    {
        $nurseryFeatures = $premiumCatalog->definitionsForNiche('nurseries');
        $defaultNurseryFeatures = app(\App\Services\Tenant\NicheCatalog::class)->defaultPremiumFeatureKeys('nurseries');

        return view('super-admin.tenants.create', [
            'nicheOptions' => $tenants->nicheSelectOptions(),
            'nurseryFeatures' => $nurseryFeatures,
            'defaultNurseryFeatures' => $defaultNurseryFeatures,
        ]);
    }

    public function store(Request $request, TenantProvisionerService $provisioner, PremiumFeatureCatalog $premiumCatalog): RedirectResponse
    {
        $allowedNiches = array_keys(config('niches.niches', []));
        $nurseryCatalogKeys = $premiumCatalog->keysForNiche('nurseries');

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'slug' => TenantSlug::createRules(),
            'niche_key' => ['required', 'string', Rule::in($allowedNiches)],
            'premium_features' => ['nullable', 'array'],
            'premium_features.*' => ['string', Rule::in($nurseryCatalogKeys)],
        ], [
            'slug.required' => 'حقل الـ slug مطلوب — اختر اسماً فريداً يدوياً.',
            'slug.regex' => 'الـ slug: حروف إنجليزية صغيرة وأرقام وشرطات فقط (مثل: retail-store).',
            'slug.unique' => 'هذا الـ slug مستخدم بالفعل — اختر اسماً آخر.',
            'niche_key.in' => 'اختر نيشاً صالحاً من القائمة.',
        ]);

        $result = $provisioner->provision($data);

        return redirect()
            ->route('super-admin.tenants.show', $result['tenant'])
            ->with('success', 'تم إنشاء المستأجر بنجاح.')
            ->with('temporary_password', $result['temporary_password']);
    }

    public function show(User $tenant, SuperAdminTenantService $tenants): View
    {
        $tenant->loadCount('employees');
        $tenant->load(['companySetting', 'tenantProfile']);
        $tenants->assertTenantAdmin($tenant);

        $enabledKeys = $tenants->enabledModuleKeysForTenant($tenant);

        return view('super-admin.tenants.show', [
            'tenant' => $tenant,
            'summary' => $tenants->tenantSummary($tenant),
            'modules' => $tenants->moduleCatalog(),
            'enabledModuleKeys' => $enabledKeys,
        ]);
    }

    public function updateModules(Request $request, User $tenant, SuperAdminTenantService $tenants): RedirectResponse
    {
        $tenants->assertTenantAdmin($tenant);

        $allowedKeys = array_keys(config('modules.modules', []));

        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in($allowedKeys)],
        ]);

        $moduleKeys = collect($data['modules'] ?? [])
            ->map(fn ($key) => strtolower((string) $key))
            ->filter(fn (string $key) => $key !== 'core')
            ->values()
            ->all();

        $tenants->syncTenantModules($tenant, $moduleKeys);

        return redirect()
            ->route('super-admin.tenants.show', $tenant)
            ->with('success', 'تم تحديث موديولات الشركة بنجاح. ستنعكس فوراً على الواجهة وواجهات الـ API.');
    }

    public function premiumFeatures(User $tenant, SuperAdminTenantService $tenants): JsonResponse
    {
        $tenants->assertTenantAdmin($tenant);
        $tenant->load(['companySetting', 'tenantProfile']);

        return response()->json($tenants->premiumFeaturePanelData($tenant));
    }

    public function updatePremiumFeatures(Request $request, User $tenant, SuperAdminTenantService $tenants): JsonResponse
    {
        $tenants->assertTenantAdmin($tenant);
        $tenant->load('tenantProfile');

        $panel = $tenants->premiumFeaturePanelData($tenant);
        $allowedKeys = $panel['catalog_keys'] ?? [];

        if ($allowedKeys === []) {
            return response()->json([
                'message' => 'لا توجد مزايا بريميوم لهذا النيش.',
            ], 422);
        }

        $data = $request->validate([
            'features' => ['nullable', 'array'],
            'features.*' => ['string', Rule::in($allowedKeys)],
        ]);

        $enabledKeys = collect($data['features'] ?? [])
            ->map(fn ($key) => strtolower((string) $key))
            ->values()
            ->all();

        try {
            $tenants->syncPremiumFeatures($tenant, $enabledKeys);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'تم حفظ المزايا البريميوم. ستنعكس فوراً على لوحة العميل.',
            'panel' => $tenants->premiumFeaturePanelData($tenant),
        ]);
    }

    public function updateSlug(Request $request, User $tenant, SuperAdminTenantService $tenants): RedirectResponse
    {
        $tenants->assertTenantAdmin($tenant);

        $data = $request->validate([
            'slug' => TenantSlug::updateRules((int) $tenant->id),
        ], [
            'slug.required' => 'حقل الـ slug مطلوب.',
            'slug.regex' => 'الـ slug: حروف إنجليزية صغيرة وأرقام وشرطات فقط (مثل: retail-store).',
            'slug.unique' => 'هذا الـ slug مستخدم بالفعل لمستأجر آخر.',
        ]);

        try {
            $tenants->updateTenantSlug($tenant, $data['slug']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['slug' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('super-admin.tenants.show', $tenant)
            ->with('success', 'تم تحديث الـ slug بنجاح. رابط المتجر: '.url('/s/'.$data['slug']));
    }
}
