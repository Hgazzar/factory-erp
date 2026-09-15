<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryProfileShellTest extends NurseryTestCase
{
    #[Test]
    public function nursery_tenant_sees_profile_inside_nursery_shell(): void
    {
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('حساب الدخول', false)
            ->assertSee('إيميل تسجيل الدخول', false)
            ->assertSee('تغيير كلمة المرور', false)
            ->assertSee('id="nurseryMobileSidebar"', false)
            ->assertDontSee('نظام إدارة الأعمال', false);
    }
}
