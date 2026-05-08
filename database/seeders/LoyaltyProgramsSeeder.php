<?php

namespace Database\Seeders;

use App\Models\CrmLoyaltyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;

class LoyaltyProgramsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->get(['id']);

        foreach ($users as $user) {
            $tenantId = (int) $user->id;

            $plans = [
                [
                    'code' => 'LOY-BRONZE-'.$tenantId,
                    'name' => 'الخطة البرونزية',
                    'name_ar' => 'البرونزية',
                    'description' => 'خطة دخول مناسبة للعملاء الجدد.',
                    'points_name' => 'نقطة برونزية',
                    'earning_rate' => 1.00,
                    'redemption_rate' => 0.0500,
                    'min_transaction_amount' => 0,
                    'min_redemption_points' => 10,
                    'max_redemption_percentage' => 10,
                    'earn_on_discounts' => false,
                    'earn_on_tax' => false,
                    'has_expiration' => false,
                    'start_date' => null,
                    'end_date' => null,
                    'tiers_count' => 1,
                    'status' => 'active',
                ],
                [
                    'code' => 'LOY-SILVER-'.$tenantId,
                    'name' => 'الخطة الفضية',
                    'name_ar' => 'الفضية',
                    'description' => 'خطة متوسطة المزايا مع خصائص أفضل من البرونزية.',
                    'points_name' => 'نقطة فضية',
                    'earning_rate' => 1.50,
                    'redemption_rate' => 0.0800,
                    'min_transaction_amount' => 0,
                    'min_redemption_points' => 10,
                    'max_redemption_percentage' => 15,
                    'earn_on_discounts' => true,
                    'earn_on_tax' => false,
                    'has_expiration' => false,
                    'start_date' => null,
                    'end_date' => null,
                    'tiers_count' => 2,
                    'status' => 'active',
                ],
                [
                    'code' => 'LOY-GOLD-'.$tenantId,
                    'name' => 'الخطة الذهبية',
                    'name_ar' => 'الذهبية',
                    'description' => 'أفضل خطة بمعدلات نقاط واستبدال أعلى.',
                    'points_name' => 'نقطة ذهبية',
                    'earning_rate' => 2.00,
                    'redemption_rate' => 0.1200,
                    'min_transaction_amount' => 0,
                    'min_redemption_points' => 10,
                    'max_redemption_percentage' => 20,
                    'earn_on_discounts' => true,
                    'earn_on_tax' => true,
                    'has_expiration' => false,
                    'start_date' => null,
                    'end_date' => null,
                    'tiers_count' => 3,
                    'status' => 'active',
                ],
            ];

            foreach ($plans as $plan) {
                CrmLoyaltyProgram::query()->updateOrCreate(
                    [
                        'user_id' => $tenantId,
                        'code' => $plan['code'],
                    ],
                    $plan
                );
            }
        }
    }
}

