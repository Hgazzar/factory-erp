<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Database\Seeder;

class JournalEntrySeeder extends Seeder
{
    public function run(): void
    {
        $accounts = Account::whereIn('code', [
            '1010', '1020', '1030', '1040', '2010', '2020', '3000',
            '4100', '4200', '5000', '6100', '6200', '6300', '6400',
        ])->pluck('id', 'code')->all();

        if (count($accounts) < 5) {
            $this->command->warn('يجب تشغيل AccountSeeder أولاً.');
            return;
        }

        $entries = [
            [
                'date' => now()->subDays(15),
                'reference' => 'JE-2024-001',
                'description' => 'إيداع نقدي في البنك',
                'lines' => [
                    ['code' => '1020', 'debit' => 50000, 'credit' => 0, 'desc' => 'إيداع من الصندوق'],
                    ['code' => '1010', 'debit' => 0, 'credit' => 50000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(14),
                'reference' => 'JE-2024-002',
                'description' => 'مبيعات آجلة للعملاء',
                'lines' => [
                    ['code' => '1030', 'debit' => 25000, 'credit' => 0, 'desc' => 'فاتورة عميل'],
                    ['code' => '4100', 'debit' => 0, 'credit' => 25000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(12),
                'reference' => 'JE-2024-003',
                'description' => 'شراء بضاعة من الموردين آجل',
                'lines' => [
                    ['code' => '1040', 'debit' => 40000, 'credit' => 0, 'desc' => 'مخزون وارد'],
                    ['code' => '2010', 'debit' => 0, 'credit' => 40000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(10),
                'reference' => 'JE-2024-004',
                'description' => 'صرف رواتب شهرية',
                'lines' => [
                    ['code' => '6100', 'debit' => 35000, 'credit' => 0, 'desc' => 'رواتب الموظفين'],
                    ['code' => '1020', 'debit' => 0, 'credit' => 35000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(8),
                'reference' => 'JE-2024-005',
                'description' => 'مصروف إيجار المكتب',
                'lines' => [
                    ['code' => '6200', 'debit' => 5000, 'credit' => 0, 'desc' => 'إيجار شهر'],
                    ['code' => '1010', 'debit' => 0, 'credit' => 5000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(6),
                'reference' => 'JE-2024-006',
                'description' => 'تحصيل من عميل',
                'lines' => [
                    ['code' => '1020', 'debit' => 15000, 'credit' => 0, 'desc' => 'تحصيل ذمم مدينة'],
                    ['code' => '1030', 'debit' => 0, 'credit' => 15000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(5),
                'reference' => 'JE-2024-007',
                'description' => 'سداد جزء للمورد',
                'lines' => [
                    ['code' => '2010', 'debit' => 20000, 'credit' => 0, 'desc' => 'سداد فاتورة'],
                    ['code' => '1020', 'debit' => 0, 'credit' => 20000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(4),
                'reference' => 'JE-2024-008',
                'description' => 'إيراد خدمات نقدي',
                'lines' => [
                    ['code' => '1010', 'debit' => 12000, 'credit' => 0, 'desc' => 'إيراد خدمات'],
                    ['code' => '4100', 'debit' => 0, 'credit' => 12000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(3),
                'reference' => 'JE-2024-009',
                'description' => 'مصروف مرافق ومستلزمات',
                'lines' => [
                    ['code' => '6300', 'debit' => 800, 'credit' => 0, 'desc' => 'كهرباء وماء'],
                    ['code' => '6400', 'debit' => 1200, 'credit' => 0, 'desc' => 'أوراق ومستلزمات'],
                    ['code' => '1020', 'debit' => 0, 'credit' => 2000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDays(2),
                'reference' => 'JE-2024-010',
                'description' => 'قيد مركب - تكلفة مبيعات وذمم',
                'lines' => [
                    ['code' => '5000', 'debit' => 18000, 'credit' => 0, 'desc' => 'تكلفة بضاعة مباعة'],
                    ['code' => '1040', 'debit' => 0, 'credit' => 18000, 'desc' => null],
                ],
            ],
            [
                'date' => now()->subDay(),
                'reference' => 'JE-2024-011',
                'description' => 'قيد افتتاحي تجريبي',
                'lines' => [
                    ['code' => '1010', 'debit' => 5000, 'credit' => 0, 'desc' => null],
                    ['code' => '4200', 'debit' => 0, 'credit' => 5000, 'desc' => 'إيراد آخر'],
                ],
            ],
        ];

        foreach ($entries as $data) {
            $total = 0;
            $items = [];
            foreach ($data['lines'] as $line) {
                $accountId = $accounts[$line['code']] ?? null;
                if (!$accountId) {
                    continue;
                }
                $debit = (float) $line['debit'];
                $credit = (float) $line['credit'];
                $total += $debit;
                $items[] = [
                    'account_id' => $accountId,
                    'description' => $line['desc'] ?? null,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            }

            if (count($items) < 2) {
                continue;
            }

            $entry = JournalEntry::create([
                'user_id' => 1,
                'date' => $data['date'],
                'reference' => $data['reference'],
                'description' => $data['description'],
                'total' => $total,
            ]);

            foreach ($items as $item) {
                $entry->items()->create($item);
            }
        }
    }
}
