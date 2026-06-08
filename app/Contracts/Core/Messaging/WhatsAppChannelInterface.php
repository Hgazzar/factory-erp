<?php

declare(strict_types=1);

namespace App\Contracts\Core\Messaging;

interface WhatsAppChannelInterface
{
    public function profile(): string;

    public function isEnabled(): bool;

    public function sendText(string $toPhone, string $message, bool $previewUrl = true): bool;

    public function sendDocument(
        string $toPhone,
        string $absolutePath,
        string $filename,
        ?string $caption = null,
    ): bool;
}
