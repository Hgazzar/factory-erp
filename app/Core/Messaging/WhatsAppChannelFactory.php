<?php

declare(strict_types=1);

namespace App\Core\Messaging;

use App\Contracts\Core\Messaging\WhatsAppChannelInterface;

final class WhatsAppChannelFactory
{
    public function __construct(
        private readonly WhatsAppConfigResolver $configResolver,
        private readonly PhoneNumberNormalizer $phoneNormalizer,
    ) {}

    public function forProfile(string $profile): WhatsAppChannelInterface
    {
        return new MetaCloudWhatsAppChannel(
            strtolower(trim($profile)),
            $this->configResolver,
            $this->phoneNormalizer,
        );
    }
}
