<?php

namespace App\Http\Controllers;

use App\Models\EinvoiceSetting;
use App\Services\ZatcaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EinvoiceSettingsController extends Controller
{
    public function edit(): View
    {
        $setting = EinvoiceSetting::get();

        return view('sales.einvoice.settings', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:zatca'],
            'environment' => ['required', 'in:sandbox,production'],
            'retry_attempts' => ['required', 'integer', 'min:0', 'max:10'],
            'retry_delay_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'enabled' => ['nullable', 'boolean'],
            'auto_send_on_issue' => ['nullable', 'boolean'],
            'zatca_tax_number' => ['nullable', 'string', 'max:20'],
            'zatca_seller_name' => ['nullable', 'string', 'max:255'],
            'zatca_seller_name_ar' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = EinvoiceSetting::get();
        $setting->provider = $data['provider'];
        $setting->environment = $data['environment'];
        $setting->retry_attempts = (int) $data['retry_attempts'];
        $setting->retry_delay_minutes = (int) $data['retry_delay_minutes'];
        $setting->enabled = ! empty($data['enabled']);
        $setting->auto_send_on_issue = ! empty($data['auto_send_on_issue']);
        $setting->zatca_tax_number = $data['zatca_tax_number'] ?? null;
        $setting->zatca_seller_name = $data['zatca_seller_name'] ?? null;
        $setting->zatca_seller_name_ar = $data['zatca_seller_name_ar'] ?? null;
        $setting->save();

        return redirect()
            ->route('sales.einvoice.settings.edit')
            ->with('success', 'تم حفظ إعدادات الفوترة الإلكترونية بنجاح.');
    }

    public function completeOnboarding(Request $request, ZatcaService $zatcaService): RedirectResponse
    {
        $data = $request->validate([
            'onboarding_otp' => ['required', 'string', 'min:4', 'max:64'],
        ], [
            'onboarding_otp.required' => 'أدخل رمز OTP من منصة فاتورة.',
        ]);

        try {
            $zatcaService->completeOnboarding(EinvoiceSetting::get(), trim($data['onboarding_otp']));
        } catch (\Throwable $e) {
            return redirect()
                ->route('sales.einvoice.settings.edit')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('sales.einvoice.settings.edit')
            ->with('success', 'تم الربط مع منصة فاتورة وحفظ شهادة الامتثال (CSID) والمفتاح بنجاح.');
    }
}
