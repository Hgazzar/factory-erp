<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\WhatsApp;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WhatsAppTest extends TestCase
{
    #[Test]
    public function digits_normalize_saudi_local_numbers(): void
    {
        $this->assertSame('966501234567', WhatsApp::digits('0501234567'));
        $this->assertSame('966501234567', WhatsApp::digits('501234567'));
        $this->assertSame('966501234567', WhatsApp::digits('+966 50 123 4567'));
        $this->assertNull(WhatsApp::digits(''));
    }

    #[Test]
    public function chat_url_requires_phone_and_encodes_text(): void
    {
        $url = WhatsApp::chatUrl('0501234567', "مرحبا\nرابط");
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/966501234567?text=', $url);
        $this->assertStringContainsString(rawurlencode("مرحبا\nرابط"), $url);
        $this->assertNull(WhatsApp::chatUrl(null, 'نص'));
    }

    #[Test]
    public function portal_draft_always_includes_portal_link_and_blank_space(): void
    {
        $draft = WhatsApp::portalDraftMessage('https://example.test/portal', 'أحمد');
        $this->assertStringContainsString('مرحباً أحمد،', $draft);
        $this->assertStringContainsString('رابط دخول بوابة ولي الأمر:', $draft);
        $this->assertStringContainsString('رقم جوالك المسجّل', $draft);
        $this->assertStringContainsString('https://example.test/portal', $draft);
        $this->assertStringContainsString("\n\n", $draft);
    }
}
