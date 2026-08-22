<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Messaging;

use App\Core\Messaging\MetaCloudWhatsAppChannel;
use App\Core\Messaging\PhoneNumberNormalizer;
use App\Core\Messaging\WhatsAppChannelFactory;
use App\Core\Messaging\WhatsAppConfigResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MetaCloudWhatsAppChannelTest extends TestCase
{
    #[Test]
    public function is_enabled_requires_token_and_phone_number_id(): void
    {
        config([
            'store.whatsapp.enabled' => true,
            'store.whatsapp.access_token' => 'token',
            'store.whatsapp.phone_number_id' => '123',
        ]);

        $channel = $this->storeChannel();

        $this->assertTrue($channel->isEnabled());

        config(['store.whatsapp.access_token' => '']);

        $this->assertFalse($this->storeChannel()->isEnabled());
    }

    #[Test]
    public function send_text_dry_runs_when_disabled(): void
    {
        config([
            'store.whatsapp.enabled' => false,
            'store.whatsapp.access_token' => '',
            'store.whatsapp.phone_number_id' => '',
            'store.whatsapp.default_country_code' => '966',
        ]);

        Http::fake();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'dry-run')
                && ($context['to'] ?? '') === '966501111111');

        $channel = $this->storeChannel();

        $this->assertTrue($channel->sendText('0501111111', 'Hello store'));
        Http::assertNothingSent();
    }

    #[Test]
    public function nursery_disabled_channel_does_not_count_as_delivered(): void
    {
        config([
            'nursery.whatsapp.enabled' => false,
            'nursery.whatsapp.access_token' => '',
            'nursery.whatsapp.phone_number_id' => '',
            'nursery.whatsapp.default_country_code' => '966',
            'clinic.whatsapp.enabled' => true,
            'clinic.whatsapp.access_token' => 'clinic-secret',
            'clinic.whatsapp.phone_number_id' => 'clinic-phone',
        ]);

        Http::fake();

        $channel = $this->nurseryChannel();

        $this->assertFalse($channel->isEnabled());
        $this->assertFalse($channel->sendText('0501111111', 'Hello nursery'));
        Http::assertNothingSent();
    }

    #[Test]
    public function nursery_config_does_not_fallback_to_clinic_credentials(): void
    {
        config([
            'clinic.whatsapp.enabled' => true,
            'clinic.whatsapp.access_token' => 'clinic-secret-token',
            'clinic.whatsapp.phone_number_id' => 'clinic-phone-id',
            'nursery.whatsapp.enabled' => true,
            'nursery.whatsapp.access_token' => '',
            'nursery.whatsapp.phone_number_id' => '',
        ]);

        $resolver = app(WhatsAppConfigResolver::class);
        $nursery = $resolver->resolve(WhatsAppConfigResolver::PROFILE_NURSERY);
        $clinic = $resolver->resolve(WhatsAppConfigResolver::PROFILE_CLINIC);

        $this->assertSame('clinic-secret-token', $clinic['access_token']);
        $this->assertTrue($clinic['enabled']);
        $this->assertSame('', $nursery['access_token']);
        $this->assertFalse($nursery['enabled']);
        $this->assertStringNotContainsString('CLINIC_WHATSAPP', (string) file_get_contents(config_path('nursery.php')));
    }

    #[Test]
    public function send_text_posts_to_meta_when_enabled(): void
    {
        config([
            'store.whatsapp.enabled' => true,
            'store.whatsapp.access_token' => 'secret-token',
            'store.whatsapp.phone_number_id' => 'phone-id-99',
            'store.whatsapp.api_version' => 'v21.0',
            'store.whatsapp.default_country_code' => '966',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]], 200),
        ]);

        $channel = $this->storeChannel();

        $this->assertTrue($channel->sendText('0502222222', 'Order confirmed'));
        $this->assertSame('wamid.1', MetaCloudWhatsAppChannel::lastProviderMessageId());

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'phone-id-99/messages')
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && ($request['to'] ?? null) === '966502222222'
                && ($request['type'] ?? null) === 'text'
                && ($request['text']['body'] ?? null) === 'Order confirmed';
        });
    }

    #[Test]
    public function factory_returns_profile_specific_channels(): void
    {
        $factory = app(WhatsAppChannelFactory::class);

        $this->assertSame('store', $factory->forProfile('store')->profile());
        $this->assertSame('clinic', $factory->forProfile('clinic')->profile());
        $this->assertSame('nursery', $factory->forProfile('nursery')->profile());
    }

    private function storeChannel(): MetaCloudWhatsAppChannel
    {
        return new MetaCloudWhatsAppChannel(
            WhatsAppConfigResolver::PROFILE_STORE,
            app(WhatsAppConfigResolver::class),
            app(PhoneNumberNormalizer::class),
        );
    }

    private function nurseryChannel(): MetaCloudWhatsAppChannel
    {
        return new MetaCloudWhatsAppChannel(
            WhatsAppConfigResolver::PROFILE_NURSERY,
            app(WhatsAppConfigResolver::class),
            app(PhoneNumberNormalizer::class),
        );
    }
}
