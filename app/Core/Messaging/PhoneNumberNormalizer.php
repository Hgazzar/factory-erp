<?php

declare(strict_types=1);

namespace App\Core\Messaging;

final class PhoneNumberNormalizer
{
    public function normalize(string $phone, string $profile, string $defaultCountryCode): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        $defaultCountry = preg_replace('/\D+/', '', $defaultCountryCode) ?? $defaultCountryCode;

        if (str_starts_with($digits, '0')) {
            $digits = $defaultCountry.ltrim($digits, '0');
        }

        $profile = strtolower(trim($profile));

        if ($profile === WhatsAppConfigResolver::PROFILE_STORE) {
            if (! str_starts_with($digits, $defaultCountry) && strlen($digits) <= 10) {
                $digits = $defaultCountry.ltrim($digits, '0');
            }
        } elseif ($profile === WhatsAppConfigResolver::PROFILE_NURSERY) {
            if (! str_starts_with($digits, $defaultCountry) && strlen($digits) === 9) {
                $digits = $defaultCountry.$digits;
            }
        }

        return $digits;
    }
}
