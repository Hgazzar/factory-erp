<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\CostCenter;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * بيانات تجريبية مؤقتة للمستخدم الحالي (إن وُجد) أو أول مستخدم في قاعدة البيانات.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $uid = (int) (Auth::check() ? Auth::id() : User::query()->orderBy('id')->value('id'));

        if ($uid <= 0 || ! User::query()->whereKey($uid)->exists()) {
            $this->command?->error('No user found: log in before seeding, or ensure at least one user exists in the users table.');

            return;
        }

        $category = ExpenseCategory::withoutGlobalScopes()->updateOrCreate(
            ['user_id' => $uid, 'code' => 'EXP-100'],
            [
                'name_ar' => 'مصاريف تشغيل',
                'name_en' => 'Operating expenses',
                'parent_id' => null,
                'is_taxable' => false,
                'status' => 'active',
            ]
        );

        $account = Account::withoutGlobalScopes()->updateOrCreate(
            ['user_id' => $uid, 'code' => 'ACC-100'],
            [
                'name_ar' => 'خزينة الكاش',
                'name_en' => 'Cash treasury',
                'type' => Account::TYPE_ASSET,
                'parent_id' => null,
                'opening_balance' => 0,
                'is_bank' => false,
                'is_active' => true,
                'allow_direct_posting' => true,
            ]
        );

        $costCenter = CostCenter::withoutGlobalScopes()->updateOrCreate(
            ['user_id' => $uid, 'code' => 'CC-100'],
            [
                'name' => 'قسم الإنتاج',
                'branch' => 'الرياض',
                'annual_budget' => 0,
                'monthly_budget' => 0,
                'status' => 'active',
                'description' => null,
            ]
        );

        for ($i = 0; $i < 10; $i++) {
            $expenseNumber = Payment::generateNextExpenseNumberForUser($uid);
            Payment::withoutGlobalScopes()->create([
                'user_id' => $uid,
                'supplier_id' => null,
                'expense_account_id' => $account->id,
                'expense_category_id' => $category->id,
                'cost_center_id' => $costCenter->id,
                'expense_number' => $expenseNumber,
                'date' => now()->subDays(9 - $i)->toDateString(),
                'reference' => 'DEMO-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'amount' => round(50 + ($i * 25.5), 2),
                'tax_amount' => 0,
                'notes' => 'بيانات تجريبية — DemoDataSeeder',
                'type' => 'expense',
                'payment_method' => 'cash',
                'journal_entry_id' => null,
                'created_by' => $uid,
            ]);
        }

        $this->command?->info('Demo data for user '.$uid.': category EXP-100, account ACC-100, cost center CC-100, 10 expense payments.');
    }
}
