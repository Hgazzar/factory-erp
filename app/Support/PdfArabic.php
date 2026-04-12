<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

/**
 * تجهيز النص العربي لعرضه في PDF عبر dompdf (ربط الحروف واتجاه RTL) باستخدام ar-php.
 */
final class PdfArabic
{
    private static ?Arabic $arabic = null;

    /**
     * @param  int  $maxLineChars  حد أطوال الأسطر داخل utf8Glyphs (تفادي تقطيع غير مرغوب)
     */
    public static function glyphs(?string $text, int $maxLineChars = 2000): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        self::$arabic ??= new Arabic;

        // hindo=false للإبقاء على الأرقام الغربية 0-9 في المستندات المحاسبية
        return self::$arabic->utf8Glyphs($text, $maxLineChars, false, true);
    }

    /** يطبّق التشكيل فقط إن وُجدت حروف عربية (لا يغيّر النصوص الإنجليزية الصرفة). */
    public static function glyphsIfArabic(?string $text, int $maxLineChars = 2000): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (! preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text)) {
            return $text;
        }

        return self::glyphs($text, $maxLineChars);
    }
}
