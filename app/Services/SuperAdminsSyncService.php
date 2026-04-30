<?php

namespace App\Services;

use App\Models\User;
use App\Support\FilamentAccess;
use Illuminate\Support\Facades\Artisan;

/**
 * مزامنة دور super_admin للبريد المعرّف؛ يُستدعى من الأمر Artisan ومن مرحلة النشر على Railway.
 */
final class SuperAdminsSyncService
{
    public const SUPER_ADMIN_ROLE = 'super_admin';

    /** @var list<string> */
    public const TARGET_EMAILS = [
        'hatem@miradasys.net',
        'info@miradasys.net',
        'profiledoorsksa@gmail.com',
    ];

    /**
     * @return array{
     *     rows: list<array{0: string, 1: string, 2: string}>,
     *     missing_filament_ids: list<int>
     * }
     */
    public function sync(bool $dryRun = false): array
    {
        $allowedFilamentIds = FilamentAccess::allowedUserIds();
        $missingFilamentIds = [];
        $rows = [];

        foreach (self::TARGET_EMAILS as $email) {
            $normalized = strtolower(trim($email));
            $user = User::query()
                ->whereRaw('LOWER(TRIM(email)) = ?', [$normalized])
                ->first();

            if ($user === null) {
                $rows[] = [$email, '—', 'غير موجود'];

                continue;
            }

            $beforeRole = (string) ($user->role ?? '');
            $needsUpdate = $beforeRole !== self::SUPER_ADMIN_ROLE;

            if ($dryRun) {
                $rows[] = [$email, (string) $user->id, $needsUpdate ? 'سيُحدَّث الدور' : 'الدور super_admin مسبقاً'];

                continue;
            }

            if ($needsUpdate) {
                $user->role = self::SUPER_ADMIN_ROLE;
                $user->save();
            }

            $rows[] = [$email, (string) $user->id, $needsUpdate ? 'تم تحديث الدور والصلاحيات المشتقة' : 'بدون تغيير'];

            if (FilamentAccess::panelIsConfigured() && ! in_array((int) $user->id, $allowedFilamentIds, true)) {
                $missingFilamentIds[] = (int) $user->id;
            }
        }

        return [
            'rows' => $rows,
            'missing_filament_ids' => array_values(array_unique($missingFilamentIds)),
        ];
    }

    /**
     * @return list<string>
     */
    public function flushPermissionCaches(): array
    {
        $lines = [];
        $commands = [
            'permission:cache-reset',
            'filament:optimize-clear',
            'cache:clear',
        ];

        foreach ($commands as $name) {
            try {
                $exit = Artisan::call($name);
                $out = trim(Artisan::output());
                $line = "تم تنفيذ: {$name} (رمز {$exit})";
                if ($out !== '') {
                    $line .= ' — '.$out;
                }
                $lines[] = $line;
            } catch (\Throwable $e) {
                $lines[] = "تخطّي {$name}: ".$e->getMessage();
            }
        }

        return $lines;
    }
}
