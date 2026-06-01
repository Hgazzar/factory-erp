<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\ClinicAccess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ClinicAccessTest extends TestCase
{
    #[Test]
    public function tenant_owner_has_full_access(): void
    {
        $access = app(ClinicAccess::class);
        $admin = new User(['role' => 'admin', 'email' => 'a@test.com']);

        $this->assertTrue($access->allows(ClinicAccess::CAP_COLLECT_PAYMENT, $admin));
        $this->assertTrue($access->allows(ClinicAccess::CAP_VIEW_CLINICAL, $admin));
        $this->assertTrue($access->allows(ClinicAccess::CAP_MANAGE_SERVICES, $admin));
    }

    #[Test]
    public function unknown_worker_has_no_clinic_capabilities(): void
    {
        $access = app(ClinicAccess::class);
        $worker = new User(['role' => 'worker', 'email' => 'w@test.com']);

        $this->assertFalse($access->allows(ClinicAccess::CAP_COLLECT_PAYMENT, $worker));
        $this->assertFalse($access->allows(ClinicAccess::CAP_VIEW_CLINICAL, $worker));
    }
}
