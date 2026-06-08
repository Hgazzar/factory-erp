<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Messaging;

use App\Core\Messaging\PhoneNumberNormalizer;
use App\Core\Messaging\WhatsAppConfigResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PhoneNumberNormalizerTest extends TestCase
{
    private PhoneNumberNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new PhoneNumberNormalizer;
    }

    #[Test]
    public function store_profile_prepends_country_for_local_numbers(): void
    {
        $this->assertSame(
            '966501111111',
            $this->normalizer->normalize('0501111111', WhatsAppConfigResolver::PROFILE_STORE, '966'),
        );
    }

    #[Test]
    public function clinic_profile_strips_leading_zero_only(): void
    {
        $this->assertSame(
            '201012345678',
            $this->normalizer->normalize('01012345678', WhatsAppConfigResolver::PROFILE_CLINIC, '20'),
        );
    }

    #[Test]
    public function nursery_profile_prepends_country_for_nine_digit_local_numbers(): void
    {
        $this->assertSame(
            '966512345678',
            $this->normalizer->normalize('512345678', WhatsAppConfigResolver::PROFILE_NURSERY, '966'),
        );
    }

    #[Test]
    public function empty_input_returns_empty_string(): void
    {
        $this->assertSame('', $this->normalizer->normalize('', WhatsAppConfigResolver::PROFILE_STORE, '966'));
        $this->assertSame('', $this->normalizer->normalize('abc', WhatsAppConfigResolver::PROFILE_STORE, '966'));
    }
}
