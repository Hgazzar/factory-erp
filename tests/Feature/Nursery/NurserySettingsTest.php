<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\NurserySetting;
use App\Models\Nursery\NurseryShift;
use App\Models\Nursery\SubscriptionPlan;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurserySettingsTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_manage_tenant_nursery_settings(): void
    {
        $this->get(route('nursery.settings.index'))
            ->assertOk()
            ->assertSee('إعدادات الحساب')
            ->assertSee('خطط الاشتراك');

        $this->put(route('nursery.settings.account.update'), [
            'nursery_name' => 'حضانة الأندلس',
            'manager_name' => 'علي محمد',
            'manager_email' => 'manager@example.com',
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'account']));

        $this->assertDatabaseHas('nursery_settings', [
            'user_id' => $this->tenant->id,
            'nursery_name' => 'حضانة الأندلس',
            'manager_name' => 'علي محمد',
        ]);

        $this->get(route('nursery.settings.index', ['tab' => 'plans']))
            ->assertOk()
            ->assertSee('شهري');

        $this->post(route('nursery.settings.plans.store'), [
            'name' => 'نصف سنوي',
            'plan_type' => 'custom',
            'amount' => 9000,
            'tax_rate' => 15,
            'currency_code' => 'SAR',
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'plans']));

        $plan = SubscriptionPlan::query()->where('user_id', $this->tenant->id)->where('name', 'نصف سنوي')->first();
        $this->assertNotNull($plan);

        $this->post(route('nursery.settings.shifts.store'), [
            'shifts' => [
                ['name' => 'صباحي', 'start_time' => '07:00', 'end_time' => '14:00'],
            ],
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'shifts']));

        $shift = NurseryShift::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($shift);
        $this->assertSame('صباحي', $shift->name);

        $settings = NurserySetting::forTenant((int) $this->tenant->id);
        $this->assertSame('حضانة الأندلس', $settings->nursery_name);

        $this->get(route('nursery.settings.index', ['tab' => 'branding']))
            ->assertOk()
            ->assertSee('تخصيص الألوان');

        $this->put(route('nursery.settings.branding.update'), [
            'display_name' => 'حضانة الألوان',
            'theme_primary_color' => '#2563eb',
            'theme_secondary_color' => '#dbeafe',
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'branding']));

        $this->assertDatabaseHas('tenant_settings', [
            'tenant_user_id' => $this->tenant->id,
            'nursery_theme_primary_color' => '#2563eb',
            'nursery_theme_secondary_color' => '#dbeafe',
        ]);

        $this->put(route('nursery.settings.branding.update'), [
            'reset_theme_colors' => '1',
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'branding']));

        $this->assertDatabaseHas('tenant_settings', [
            'tenant_user_id' => $this->tenant->id,
            'nursery_theme_primary_color' => null,
            'nursery_theme_secondary_color' => null,
        ]);

        $logo = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 120, 120);
        $this->put(route('nursery.settings.branding.update'), [
            'display_name' => 'حضانة الشعار',
            'theme_primary_color' => '#ea580c',
            'theme_secondary_color' => '#ffedd5',
            'logo_file' => $logo,
        ])->assertRedirect(route('nursery.settings.index', ['tab' => 'branding']));

        $setting = \App\Models\TenantSetting::query()->where('tenant_user_id', $this->tenant->id)->first();
        $this->assertNotNull($setting);
        $this->assertNotEmpty($setting->logo_path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($setting->logo_path));
    }
}
