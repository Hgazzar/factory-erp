<?php

declare(strict_types=1);

namespace App\Core\Messaging;

final class WhatsAppConfigResolver
{
    public const PROFILE_STORE = 'store';

    public const PROFILE_CLINIC = 'clinic';

    public const PROFILE_NURSERY = 'nursery';

    /**
     * @return array{
     *   enabled: bool,
     *   access_token: string,
     *   phone_number_id: string,
     *   api_version: string,
     *   default_country_code: string
     * }
     */
    public function resolve(string $profile): array
    {
        $profile = strtolower(trim($profile));

        /** @var array<string, mixed> $raw */
        $raw = match ($profile) {
            self::PROFILE_CLINIC => config('clinic.whatsapp', []),
            self::PROFILE_NURSERY => config('nursery.whatsapp', []),
            default => config('store.whatsapp', []),
        };

        $accessToken = trim((string) ($raw['access_token'] ?? ''));
        $phoneNumberId = trim((string) ($raw['phone_number_id'] ?? ''));

        return [
            'enabled' => (bool) ($raw['enabled'] ?? false)
                && $accessToken !== ''
                && $phoneNumberId !== '',
            'access_token' => $accessToken,
            'phone_number_id' => $phoneNumberId,
            'api_version' => (string) ($raw['api_version'] ?? 'v21.0'),
            'default_country_code' => (string) ($raw['default_country_code'] ?? '966'),
        ];
    }
}
