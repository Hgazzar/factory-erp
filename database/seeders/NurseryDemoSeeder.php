<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\Nursery\CalendarEntry;
use App\Models\Nursery\Child;
use App\Models\Nursery\ChildMedication;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\Enrollment;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurserySetting;
use App\Models\Nursery\Subscription;
use App\Models\Nursery\SubscriptionPlan;
use App\Models\TenantFeature;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\ChartOfAccountsProvisioner;
use App\Services\Tenant\NicheCatalog;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Services\Tenant\TenantModuleRegistry;
use App\Support\PremiumFeatureKeys;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * حضانة تجريبية لاختبار بوابة أولياء الأمور محلياً.
 *
 * تشغيل: php artisan db:seed --class=NurseryDemoSeeder
 */
final class NurseryDemoSeeder extends Seeder
{
    public const SLUG = 'demo-nursery';

    public const EMAIL = 'nursery-demo@akwad.test';

    public const PASSWORD = 'password';

    public const GUARDIAN_PHONE = '0500000000';

    /** رابط دعوة ثابت للتجربة بدون OTP */
    public const PORTAL_ACCESS_TOKEN = 'demo-nursery-parent-portal-token-fixed';

    public function run(): void
    {
        $this->call(SystemModuleSeeder::class);

        $nicheCatalog = app(NicheCatalog::class);
        $moduleRegistry = app(TenantModuleRegistry::class);
        $featureRegistry = app(TenantFeatureRegistry::class);

        $tenant = DB::transaction(function () use ($nicheCatalog, $moduleRegistry): User {
            $profile = TenantProfile::query()
                ->where('slug', self::SLUG)
                ->orWhere('domain', self::SLUG)
                ->first();

            if ($profile !== null) {
                $tenant = User::query()->findOrFail((int) $profile->tenant_user_id);
            } else {
                $tenant = User::query()->firstOrCreate(
                    ['email' => self::EMAIL],
                    [
                        'name' => 'مدير حضانة تجريبية',
                        'role' => 'admin',
                        'password' => Hash::make(self::PASSWORD),
                    ],
                );

                CompanySetting::query()->firstOrCreate(
                    ['user_id' => $tenant->id],
                    ['name' => 'حضانة أكواد التجريبية'],
                );

                $profile = TenantProfile::query()->firstOrNew(['tenant_user_id' => (int) $tenant->id]);
            }

            $profile->fill([
                'tenant_user_id' => (int) $tenant->id,
                'niche_key' => 'nurseries',
                'domain' => self::SLUG,
                'slug' => self::SLUG,
                'status' => TenantProfile::STATUS_ACTIVE,
            ]);
            $profile->save();

            $moduleKeys = $nicheCatalog->defaultModuleKeys('nurseries');
            $moduleRegistry->syncModulesForTenant((int) $tenant->id, $moduleKeys);

            ChartOfAccountsProvisioner::ensureForUser((int) $tenant->id);

            NurserySetting::query()->updateOrCreate(
                ['user_id' => (int) $tenant->id],
                [
                    'nursery_name' => 'حضانة أكواد التجريبية',
                    'contact_phone' => self::GUARDIAN_PHONE,
                    'contact_email' => self::EMAIL,
                    'city' => 'الرياض',
                    'region' => 'riyadh',
                ],
            );

            $guardian = Guardian::query()->firstOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'phone' => self::GUARDIAN_PHONE,
                ],
                [
                    'name' => 'ولي أمر تجريبي',
                    'email' => 'guardian-demo@akwad.test',
                ],
            );

            $guardian->forceFill([
                'portal_access_token' => self::PORTAL_ACCESS_TOKEN,
                'portal_invited_at' => now(),
            ])->save();

            $classroom = Classroom::query()->firstOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'name' => 'فصل البراعم (تجريبي)',
                ],
                [
                    'capacity' => 20,
                    'is_active' => true,
                ],
            );

            $child = Child::query()->updateOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'code' => 'DEMO-001',
                ],
                [
                    'name' => 'طفل تجريبي',
                    'gender' => 'female',
                    'guardian_id' => $guardian->id,
                    'guardian_relationship' => 'mother',
                    'status' => Child::STATUS_ACTIVE,
                ],
            );

            Enrollment::query()->updateOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'child_id' => $child->id,
                    'is_active' => true,
                ],
                [
                    'classroom_id' => $classroom->id,
                    'starts_on' => now()->toDateString(),
                    'ends_on' => null,
                ],
            );

            ChildMedication::query()->firstOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'child_id' => $child->id,
                    'name' => 'فيتامين د',
                ],
                [
                    'dosage' => '5 قطرات',
                    'frequency' => ChildMedication::FREQ_ONCE_DAILY,
                    'schedule_notes' => 'بعد الغداء',
                    'sort_order' => 0,
                ],
            );

            $plan = SubscriptionPlan::query()->firstOrCreate(
                ['user_id' => (int) $tenant->id, 'name' => 'شهري تجريبي'],
                ['plan_type' => 'monthly', 'amount' => 1000, 'tax_rate' => 15, 'is_active' => true],
            );

            Subscription::query()->firstOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'child_id' => $child->id,
                    'plan_id' => $plan->id,
                    'starts_on' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'ends_on' => now()->endOfMonth()->toDateString(),
                    'amount_after_tax' => 1150,
                    'discount_amount' => 0,
                    'is_paid' => true,
                    'status' => Subscription::STATUS_ACTIVE,
                ],
            );

            $weekStart = now()->startOfWeek(\Carbon\Carbon::SATURDAY);
            CalendarEntry::query()->firstOrCreate(
                [
                    'user_id' => (int) $tenant->id,
                    'title' => 'نشاط الرسم',
                    'starts_at' => $weekStart->copy()->setTime(10, 0),
                ],
                [
                    'entry_type' => CalendarEntry::TYPE_ACTIVITY,
                    'ends_at' => $weekStart->copy()->setTime(11, 0),
                    'classroom_ids' => [$classroom->id],
                    'notes' => 'فعالية تجريبية للبوابة',
                ],
            );

            return $tenant->fresh();
        });

        $tenantId = (int) $tenant->id;

        foreach ([
            PremiumFeatureKeys::NURSERY_PARENT_PORTAL,
            PremiumFeatureKeys::NURSERY_WHATSAPP_AUTOMATION,
            PremiumFeatureKeys::NURSERY_SUBSCRIPTION_FINANCE,
        ] as $featureKey) {
            TenantFeature::query()->firstOrCreate([
                'tenant_id' => $tenantId,
                'feature_key' => $featureKey,
            ]);
        }

        $moduleRegistry->forgetCache($tenantId);
        $featureRegistry->forgetCache($tenantId);

        $portalLogin = route('nursery.portal.login', ['tenant_slug' => self::SLUG]);
        $portalInvite = route('nursery.portal.invite', [
            'tenant_slug' => self::SLUG,
            'token' => self::PORTAL_ACCESS_TOKEN,
        ]);

        $this->command?->info('✓ Nursery demo tenant ready.');
        $this->command?->line("  Tenant user id: {$tenantId}");
        $this->command?->line('  Admin login: '.self::EMAIL.' / '.self::PASSWORD);
        $this->command?->line('  Portal (OTP): '.$portalLogin);
        $this->command?->line('  Guardian phone: '.self::GUARDIAN_PHONE.' — OTP dev: '.config('nursery.portal.dev_otp_code', '123456'));
        $this->command?->line('  Portal (invite link): '.$portalInvite);
    }
}
