<?php

namespace App\Console\Commands;

use App\Services\SuperAdminsSyncService;
use Illuminate\Console\Command;

/**
 * يعتمد النظام على عمود users.role (مثل super_admin) وليس حزمة Spatie؛
 * هذا الأمر يحدّث الدور ثم يحاول تفريغ كاشات الصلاحيات/الإضافات عند توفر الأوامر.
 */
class SetSuperAdminsCommand extends Command
{
    protected $signature = 'system:set-super-admins {--dry-run : عرض دون تحديث قاعدة البيانات}';

    protected $description = 'منح دور super_admin للمستخدمين المحددين بالبريد، ثم إعادة تفريغ كاش الصلاحيات/الإضافات عند توفر الأوامر.';

    public function handle(SuperAdminsSyncService $sync): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $sync->sync($dryRun);

        $this->table(['البريد', 'المعرّف', 'الحالة'], $result['rows']);

        if ($dryRun) {
            $this->comment('وضع المعاينة: لم يُحفظ أي تغيير.');
        } else {
            foreach ($sync->flushPermissionCaches() as $line) {
                $this->line($line);
            }

            if ($result['missing_filament_ids'] !== []) {
                $ids = implode(',', $result['missing_filament_ids']);
                $this->warn('لوحة Filament تعتمد على FILAMENT_ALLOWED_USER_IDS. أضف المعرفات التالية في الإعدادات إن لزم: '.$ids);
            }
        }

        $this->info('اكتمل.');

        return self::SUCCESS;
    }
}
