<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Nursery\NurserySetting;
use App\Models\Nursery\NurseryShift;
use App\Models\Nursery\SubscriptionPlan;
use App\Models\TenantProfile;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryPortalInviteService;
use App\Services\Nursery\NurserySettingsService;
use App\Services\Nursery\NurseryShiftService;
use App\Services\Nursery\NurserySubscriptionPlanService;
use App\Services\Nursery\NurserySubscriptionService;
use App\Services\Nursery\NurseryTenantFeaturesService;
use App\Support\NurseryAccess;
use App\Support\SaudiRegions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurserySettingsWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(
        Request $request,
        NurserySettingsService $settingsService,
        NurseryDashboardService $dashboard,
        NurserySubscriptionPlanService $planService,
        NurseryShiftService $shiftService,
        NurserySubscriptionService $subscriptions,
        NurseryTenantFeaturesService $tenantFeatures,
    ): View {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $tab = $this->normalizeTab($request->query('tab', 'account'));

        $settings = NurserySetting::forTenant($tenantUserId);
        $subscriptions->ensureDefaultPlans($tenantUserId);

        $data = [
            'tab' => $tab,
            'settings' => $settings,
            'joinedAt' => $settingsService->joinedAt($tenantUserId),
            'overview' => $dashboard->overviewStats($tenantUserId),
            'canManage' => app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_SETTINGS),
            'regionOptions' => SaudiRegions::regionSelectOptions(),
            'cityOptions' => SaudiRegions::citySelectOptions($settings->region),
            'regionLabel' => SaudiRegions::regionLabel($settings->region),
        ];

        if ($tab === 'plans') {
            $data['plans'] = $planService->listForTenant($tenantUserId);
            $data['planTypeOptions'] = collect($planService->planTypeOptions())
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all();
            $data['currencyOptions'] = collect($planService->currencyOptions())
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all();
        }

        if ($tab === 'shifts') {
            $data['shifts'] = $shiftService->listForTenant($tenantUserId);
        }

        if ($tab === 'features') {
            $data['featurePanel'] = $tenantFeatures->panelForTenant($tenantUserId);
        }

        if ($tab === 'branding') {
            $profile = TenantProfile::forTenantUser($tenantUserId);
            $slug = $profile?->slug ?? $profile?->domain;
            $data['tenantSlug'] = $slug;
            $data['portalUrl'] = $slug
                ? app(NurseryPortalInviteService::class)->portalLoginUrl($tenantUserId)
                : null;
            $data['brandingDisplayName'] = \App\Models\TenantSetting::forTenant($tenantUserId)->display_name;
        }

        return view('nursery.settings.index', $data);
    }

    public function citySelectPartial(Request $request): View
    {
        $regionKey = trim((string) $request->query('region', ''));
        $cityValue = trim((string) $request->query('city', ''));

        return view('nursery.settings.partials.city-select', compact('regionKey', 'cityValue'));
    }

    public function updateAccount(Request $request, NurserySettingsService $settingsService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'nursery_name' => ['required', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:64'],
            'city' => ['nullable', 'string', 'max:120'],
            'manager_name' => ['nullable', 'string', 'max:120'],
            'manager_mobile' => ['nullable', 'string', 'max:32'],
            'manager_email' => ['nullable', 'email', 'max:120'],
        ]);

        try {
            $settingsService->updateAccount($tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'account'])
            ->with('success', 'تم حفظ بيانات الحضانة.');
    }

    public function updateBranding(Request $request, NurserySettingsService $settingsService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'theme_primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'reset_theme_colors' => ['nullable', 'boolean'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        try {
            $settingsService->updateBranding($tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $settingsService->updateLogo(
            $tenantUserId,
            $request->file('logo_file'),
            $request->boolean('remove_logo'),
        );

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'branding'])
            ->with('success', 'تم حفظ الهوية البصرية والألوان.');
    }

    public function storePlan(Request $request, NurserySubscriptionPlanService $planService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'plan_type' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency_code' => ['required', 'string', 'max:8'],
        ]);

        try {
            $planService->create($tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'plans'])
            ->with('success', 'تمت إضافة خطة الاشتراك.');
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan, NurserySubscriptionPlanService $planService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'plan_type' => ['required', 'string', 'max:32'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'currency_code' => ['required', 'string', 'max:8'],
        ]);

        try {
            $planService->update($plan, $tenantUserId, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'plans'])
            ->with('success', 'تم تحديث خطة الاشتراك.');
    }

    public function destroyPlan(SubscriptionPlan $plan, NurserySubscriptionPlanService $planService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        try {
            $planService->deactivate($plan, $tenantUserId);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'plans'])
            ->with('success', 'تم إلغاء تفعيل الخطة.');
    }

    public function storeShifts(Request $request, NurseryShiftService $shiftService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate([
            'shifts' => ['required', 'array', 'min:1'],
            'shifts.*.name' => ['nullable', 'string', 'max:120'],
            'shifts.*.start_time' => ['nullable', 'string', 'max:8'],
            'shifts.*.end_time' => ['nullable', 'string', 'max:8'],
        ]);

        try {
            $shiftService->createBatch($tenantUserId, $data['shifts']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'shifts'])
            ->with('success', 'تمت إضافة المناوبات.');
    }

    public function destroyShift(NurseryShift $shift, NurseryShiftService $shiftService): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $shiftService->deactivate($shift, $tenantUserId);

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'shifts'])
            ->with('success', 'تم حذف المناوبة.');
    }

    public function updateFeatures(Request $request, NurseryTenantFeaturesService $tenantFeatures): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $panel = $tenantFeatures->panelForTenant($tenantUserId);
        $allowedKeys = $panel['catalog_keys'] ?? [];

        $data = $request->validate([
            'features' => ['nullable', 'array'],
            'features.*' => ['string', \Illuminate\Validation\Rule::in($allowedKeys)],
        ]);

        $enabledKeys = collect($data['features'] ?? [])
            ->map(fn ($key) => strtolower((string) $key))
            ->values()
            ->all();

        try {
            $tenantFeatures->syncForTenant($tenantUserId, $enabledKeys);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('nursery.settings.index', ['tab' => 'features'])
            ->with('success', 'تم حفظ مزايا الحضانة.');
    }

    private function normalizeTab(mixed $tab): string
    {
        $tab = strtolower(trim((string) $tab));

        return in_array($tab, ['account', 'branding', 'plans', 'shifts', 'features'], true) ? $tab : 'account';
    }
}
