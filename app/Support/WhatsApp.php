<?php

declare(strict_types=1);

namespace App\Support;

/**
 * روابط واتساب يدوية (wa.me) — يفتح التطبيق برقم ورسالة جاهزة قابلة للتعديل قبل الإرسال.
 */
final class WhatsApp
{
    public static function digits(?string $phone, string $defaultCountryCode = '966'): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $country = preg_replace('/\D+/', '', $defaultCountryCode) ?: '966';

        if (str_starts_with($digits, '0')) {
            $digits = $country.ltrim($digits, '0');
        } elseif (! str_starts_with($digits, $country) && strlen($digits) === 9) {
            $digits = $country.$digits;
        }

        return $digits !== '' ? $digits : null;
    }

    public static function chatUrl(?string $phone, ?string $text = null, string $defaultCountryCode = '966'): ?string
    {
        $digits = self::digits($phone, $defaultCountryCode);
        if ($digits === null) {
            return null;
        }

        $url = 'https://wa.me/'.$digits;
        if ($text !== null && $text !== '') {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }

    /**
     * مسودة رسالة عامة: مساحة فارغة للكتابة + رابط البوابة دائماً.
     */
    public static function portalDraftMessage(?string $portalUrl, ?string $guardianName = null): string
    {
        $name = trim((string) $guardianName) ?: 'ولي الأمر';

        $lines = [
            "مرحباً {$name}،",
            '',
            '',
        ];

        if (filled($portalUrl)) {
            $lines[] = 'رابط دخول بوابة ولي الأمر:';
            $lines[] = $portalUrl;
            $lines[] = 'بعد فتح الرابط أدخل رقم جوالك المسجّل لدى الحضانة.';
        }

        $lines[] = '';
        $lines[] = '— اكتب رسالتك في المساحة الفارغة أعلاه إن رغبت، ثم أرسل —';

        return implode("\n", $lines);
    }
}
