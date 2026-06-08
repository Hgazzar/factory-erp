<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\TenantProfile;
use App\Services\Tenant\TenantBrandingService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicSettingsWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly TenantBrandingService $brandingService,
        private readonly ClinicAccess $clinicAccess,
        private readonly TenantFeatureRegistry $features,
    ) {}

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $tab = $this->normalizeTab((string) $request->query('tab', 'branding'));

        $branding = $this->brandingService->branding($tenantUserId, null, TenantBrandingService::MODULE_CLINIC);
        $profile = TenantProfile::forTenantUser($tenantUserId);
        $tenantSlug = $profile?->slug ?? $profile?->domain;

        $portalUrl = null;
        if ($tenantSlug && $this->features->isEnabled('clinic_patient_portal', $tenantUserId)) {
            $portalUrl = route('clinic.portal.book', ['tenant_slug' => $tenantSlug]);
        }

        $tenantSetting = \App\Models\TenantSetting::forTenant($tenantUserId);

        return view('clinic.settings.index', [
            'tab' => $tab,
            'branding' => $branding,
            'canManage' => $this->clinicAccess->isTenantOwner(),
            'portalUrl' => $portalUrl,
            'tenantSlug' => $tenantSlug,
            'portalPathHint' => $tenantSlug ? '/clinic-portal/'.$tenantSlug : null,
            'displayNameValue' => old('display_name', $tenantSetting->display_name),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        if (! $this->clinicAccess->isTenantOwner()) {
            abort(403);
        }

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
            $this->brandingService->updateBranding($tenantUserId, $data, TenantBrandingService::MODULE_CLINIC);
            $this->brandingService->updateLogo(
                $tenantUserId,
                $request->file('logo_file'),
                $request->boolean('remove_logo'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('clinic.settings.index', ['tab' => 'branding'])
            ->with('success', 'تم حفظ الهوية البصرية للعيادة.');
    }

    private function normalizeTab(string $tab): string
    {
        return $tab === 'branding' ? 'branding' : 'branding';
    }
}
