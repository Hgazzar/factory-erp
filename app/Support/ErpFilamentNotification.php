<?php

namespace App\Support;

use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * تنبيهات نجاح موحّدة (Filament) للواجهة العربية RTL.
 */
final class ErpFilamentNotification
{
    public static function success(string $title, ?string $body = null, ?int $durationMs = null): void
    {
        $n = Notification::make()
            ->title($title)
            ->success()
            ->icon(Heroicon::OutlinedCheckCircle);

        if ($body !== null && $body !== '') {
            $n->body($body);
        }

        if ($durationMs !== null) {
            $n->duration($durationMs);
        }

        $n->send();
    }

    /**
     * استيراد Excel/CSV: بقاء 6 ثوانٍ مع تفاصيل الأعداد.
     */
    public static function successImport(string $title, string $body): void
    {
        self::success($title, $body, 6000);
    }

    /**
     * تحويل رسالة session('success') القديمة إلى تنبيه Filament.
     */
    public static function fromLegacyFlashMessage(string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $isImport = str_contains($message, 'استيراد')
            && (str_contains($message, 'نجاح:') || str_contains($message, 'إضافة') || str_contains($message, 'تحديث'));

        if ($isImport) {
            self::successImport('تم الاستيراد بنجاح', $message);

            return;
        }

        if (mb_strlen($message) <= 96) {
            self::success($message);

            return;
        }

        self::success('تم بنجاح', $message);
    }
}
