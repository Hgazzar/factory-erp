<?php

namespace App\Console\Commands;

use App\Services\OrderWarehouseBackfillService;
use Illuminate\Console\Command;

/**
 * تعيين مستودع افتراضي لأوامر إنتاج/توريد قديمة بلا warehouse_id.
 * استخدم --dry-run أولاً؛ التنفيذ الفعلي يتطلب --force.
 */
class BackfillOrderWarehouseIdsCommand extends Command
{
    protected $signature = 'erp:backfill-order-warehouses
                            {--dry-run : معاينة دون تحديث (افتراضي إن لم يُمرَّر --force)}
                            {--force : تنفيذ التحديث على قاعدة البيانات}
                            {--user-id= : تقييد الترحيل لمستأجر واحد (users.id)}';

    protected $description = 'تعيين المستودع الافتراضي (warehouses.is_default) لأوامر إنتاج/توريد الناقصة';

    public function handle(OrderWarehouseBackfillService $backfill): int
    {
        $force = (bool) $this->option('force');
        $dryRun = ! $force || (bool) $this->option('dry-run');

        if ($force && $this->option('dry-run')) {
            $this->warn('تم تمرير --force و --dry-run معاً؛ سيتم التنفيذ الفعلي (--force يتقدم).');
            $dryRun = false;
        }

        $onlyUserId = $this->option('user-id');
        $onlyUserId = is_numeric($onlyUserId) ? (int) $onlyUserId : null;

        if ($dryRun) {
            $this->info('وضع المعاينة — لن يُحفظ أي تغيير. للتنفيذ: php artisan erp:backfill-order-warehouses --force');
        } else {
            if (! $this->confirm('تأكيد تعيين المستودعات على الأوامر الناقصة؟', true)) {
                $this->comment('أُلغي.');

                return self::SUCCESS;
            }
        }

        $result = $backfill->run($dryRun, $onlyUserId);

        if ($result['production'] !== []) {
            $this->newLine();
            $this->line('<fg=cyan>أوامر الإنتاج</>');
            $this->table(
                ['#', 'رقم الأمر', 'مستأجر', 'مستودع', 'حالة', 'ملاحظة'],
                array_map(static fn (array $r) => [
                    $r['order_id'],
                    $r['production_number'],
                    $r['user_id'] ?? '—',
                    $r['warehouse_id'] ?? '—',
                    $r['status'],
                    $r['note'],
                ], $result['production'])
            );
        } else {
            $this->line('لا توجد أوامر إنتاج تحتاج تعيين مستودع.');
        }

        if ($result['delivery'] !== []) {
            $this->newLine();
            $this->line('<fg=cyan>أوامر التوريد</>');
            $this->table(
                ['#', 'رقم التوريد', 'مستأجر', 'مستودع', 'حالة', 'ملاحظة'],
                array_map(static fn (array $r) => [
                    $r['order_id'],
                    $r['delivery_number'],
                    $r['user_id'],
                    $r['warehouse_id'] ?? '—',
                    $r['status'],
                    $r['note'],
                ], $result['delivery'])
            );
        } else {
            $this->line('لا توجد أوامر توريد تحتاج تعيين مستودع.');
        }

        $this->newLine();
        $this->table(
            ['النوع', 'سيُحدَّث / عُولج', 'تخطي'],
            [
                ['إنتاج', (string) $result['updated_production'], (string) $result['skipped_production']],
                ['توريد', (string) $result['updated_delivery'], (string) $result['skipped_delivery']],
            ]
        );

        $this->comment('ملاحظة: هذا لا يعدّل item_warehouse ولا stock_movements — فقط حقول المستودع على الأمر.');

        if ($result['skipped_production'] > 0 || $result['skipped_delivery'] > 0) {
            $this->warn('راجع الصفوف المتخطاة: عيّن warehouses.is_default أو أنشئ مستودعاً نشطاً لكل مستأجر.');
        }

        return self::SUCCESS;
    }
}
