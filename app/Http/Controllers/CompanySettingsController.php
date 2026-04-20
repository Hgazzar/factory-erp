<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        $setting = CompanySetting::first() ?? new CompanySetting;
        return view('settings.company', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'default_vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = CompanySetting::first();
        if (!$setting) {
            $setting = new CompanySetting;
        }

        $setting->name = $data['name'] ?? null;
        $setting->tax_number = $data['tax_number'] ?? null;
        $setting->default_vat_percent = (float) $data['default_vat_percent'];
        $setting->commercial_register = $data['commercial_register'] ?? null;
        $setting->address = $data['address'] ?? null;

        if ($request->hasFile('logo_file')) {
            $oldPath = $setting->logo_url && str_starts_with($setting->logo_url, 'company/')
                ? $setting->logo_url
                : null;
            $path = $request->file('logo_file')->store('company', 'public');
            $setting->logo_url = $path;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        } elseif (array_key_exists('logo_url', $data)) {
            $setting->logo_url = $data['logo_url'] ?: null;
        }

        $setting->save();

        return redirect()
            ->route('settings.company.edit')
            ->with('success', 'تم حفظ إعدادات المنشأة بنجاح.');
    }
}
