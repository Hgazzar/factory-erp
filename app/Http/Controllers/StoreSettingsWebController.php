<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\PosDevice;
use App\Models\TenantProfile;
use App\Models\TenantStoreSetting;
use App\Services\Store\StoreMerchantMetricsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class StoreSettingsWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function edit(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $settings = TenantStoreSetting::forTenant($tenantUserId);
        $profile = TenantProfile::forTenantUser($tenantUserId);

        $devices = PosDevice::query()
            ->where('user_id', $tenantUserId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $storeUrl = $profile
            ? route('store.portal.home', ['tenant_slug' => $profile->slug ?? $profile->domain])
            : null;

        $merchantMetrics = app(StoreMerchantMetricsService::class)->snapshot($tenantUserId);

        return view('settings.store', compact('settings', 'devices', 'storeUrl', 'profile', 'merchantMetrics'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $validated = $request->validate([
            'is_store_enabled' => ['nullable', 'boolean'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:255'],
            'hero_offer_text' => ['nullable', 'string', 'max:2000'],
            'about_us' => ['nullable', 'string', 'max:50000'],
            'contact_us' => ['nullable', 'string', 'max:50000'],
            'faq' => ['nullable', 'string', 'max:50000'],
            'shipping_policy' => ['nullable', 'string', 'max:50000'],
            'privacy_policy' => ['nullable', 'string', 'max:50000'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_twitter' => ['nullable', 'string', 'max:255'],
            'social_whatsapp' => ['nullable', 'string', 'max:64'],
            'default_pos_device_id' => ['nullable', 'integer', 'exists:pos_devices,id'],
        ]);

        $settings = TenantStoreSetting::forTenant($tenantUserId);

        $deviceId = isset($validated['default_pos_device_id']) ? (int) $validated['default_pos_device_id'] : null;
        if ($deviceId !== null && $deviceId > 0) {
            $ownsDevice = PosDevice::query()
                ->where('user_id', $tenantUserId)
                ->whereKey($deviceId)
                ->exists();
            if (! $ownsDevice) {
                return back()->withErrors(['default_pos_device_id' => 'الجهاز غير صالح.'])->withInput();
            }
        } else {
            $deviceId = null;
        }

        $settings->fill([
            'is_store_enabled' => $request->boolean('is_store_enabled'),
            'hero_title' => $validated['hero_title'] ?? null,
            'hero_subtitle' => $validated['hero_subtitle'] ?? null,
            'hero_offer_text' => $validated['hero_offer_text'] ?? null,
            'about_us' => $validated['about_us'] ?? null,
            'contact_us' => $validated['contact_us'] ?? null,
            'faq' => $validated['faq'] ?? null,
            'shipping_policy' => $validated['shipping_policy'] ?? null,
            'privacy_policy' => $validated['privacy_policy'] ?? null,
            'social_facebook' => $validated['social_facebook'] ?? null,
            'social_instagram' => $validated['social_instagram'] ?? null,
            'social_twitter' => $validated['social_twitter'] ?? null,
            'social_whatsapp' => $validated['social_whatsapp'] ?? null,
            'default_pos_device_id' => $deviceId,
        ]);
        $settings->save();

        return redirect()
            ->route('settings.store.edit')
            ->with('success', 'تم حفظ إعدادات المتجر الإلكتروني.');
    }
}
