<?php

declare(strict_types=1);

namespace App\Core\Messaging;

use App\Contracts\Core\Messaging\WhatsAppChannelInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class MetaCloudWhatsAppChannel implements WhatsAppChannelInterface
{
    private static ?string $lastProviderMessageId = null;

    public function __construct(
        private readonly string $profile,
        private readonly WhatsAppConfigResolver $configResolver,
        private readonly PhoneNumberNormalizer $phoneNormalizer,
    ) {}

    public static function lastProviderMessageId(): ?string
    {
        return self::$lastProviderMessageId;
    }

    public function profile(): string
    {
        return $this->profile;
    }

    public function isEnabled(): bool
    {
        return $this->configResolver->resolve($this->profile)['enabled'];
    }

    public function sendText(string $toPhone, string $message, bool $previewUrl = true): bool
    {
        $to = $this->phoneNormalizer->normalize(
            $toPhone,
            $this->profile,
            $this->configResolver->resolve($this->profile)['default_country_code'],
        );

        if ($to === '') {
            Log::warning("WhatsApp [{$this->profile}]: empty recipient phone.");
            self::$lastProviderMessageId = null;

            return false;
        }

        if (! $this->isEnabled()) {
            if ($this->profile === WhatsAppConfigResolver::PROFILE_NURSERY) {
                Log::info("WhatsApp [{$this->profile}] skipped_config: channel disabled, not delivered", [
                    'to' => $to,
                ]);
                self::$lastProviderMessageId = null;

                return false;
            }

            Log::info("WhatsApp [{$this->profile}] (dry-run): would send message", [
                'to' => $to,
                'message' => $message,
            ]);

            return true;
        }

        return $this->postMessage($to, [
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $message,
            ],
        ]);
    }

    public function sendDocument(
        string $toPhone,
        string $absolutePath,
        string $filename,
        ?string $caption = null,
    ): bool {
        $to = $this->phoneNormalizer->normalize(
            $toPhone,
            $this->profile,
            $this->configResolver->resolve($this->profile)['default_country_code'],
        );

        if ($to === '' || ! is_file($absolutePath)) {
            return false;
        }

        if (! $this->isEnabled()) {
            if ($this->profile === WhatsAppConfigResolver::PROFILE_NURSERY) {
                Log::info("WhatsApp [{$this->profile}] skipped_config: document not delivered", [
                    'to' => $to,
                    'filename' => $filename,
                ]);

                return false;
            }

            Log::info("WhatsApp [{$this->profile}] (dry-run): would send document", [
                'to' => $to,
                'filename' => $filename,
                'caption' => $caption,
                'path' => $absolutePath,
            ]);

            return true;
        }

        $mediaId = $this->uploadMedia($absolutePath, 'application/pdf');
        if ($mediaId === null) {
            return false;
        }

        return $this->postMessage($to, [
            'type' => 'document',
            'document' => array_filter([
                'id' => $mediaId,
                'filename' => $filename,
                'caption' => $caption,
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postMessage(string $to, array $payload): bool
    {
        $config = $this->configResolver->resolve($this->profile);
        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $config['api_version'],
            $config['phone_number_id'],
        );

        self::$lastProviderMessageId = null;

        try {
            $response = Http::withToken($config['access_token'])
                ->timeout(20)
                ->post($url, array_merge([
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                ], $payload));

            if ($response->failed()) {
                Log::error("WhatsApp [{$this->profile}] API error", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'to' => $to,
                ]);

                return false;
            }

            $id = $response->json('messages.0.id');
            if (is_string($id) && $id !== '') {
                self::$lastProviderMessageId = $id;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("WhatsApp [{$this->profile}] send failed", [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);

            return false;
        }
    }

    private function uploadMedia(string $absolutePath, string $mime): ?string
    {
        $config = $this->configResolver->resolve($this->profile);
        $url = sprintf(
            'https://graph.facebook.com/%s/%s/media',
            $config['api_version'],
            $config['phone_number_id'],
        );

        try {
            $response = Http::withToken($config['access_token'])
                ->timeout(30)
                ->attach('file', file_get_contents($absolutePath) ?: '', basename($absolutePath))
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mime,
                ]);

            if ($response->failed()) {
                Log::error("WhatsApp [{$this->profile}] media upload failed", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $id = $response->json('id');

            return is_string($id) && $id !== '' ? $id : null;
        } catch (\Throwable $e) {
            Log::error("WhatsApp [{$this->profile}] media upload exception", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
