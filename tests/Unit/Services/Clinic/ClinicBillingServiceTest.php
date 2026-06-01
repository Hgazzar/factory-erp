<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Clinic;

use App\Models\CompanySetting;
use App\Services\Clinic\ClinicBillingService;
use App\Services\Clinic\ClinicServiceCatalogService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicBillingServiceTest extends ClinicTestCase
{
    #[Test]
    public function quote_calculates_vat_from_services(): void
    {
        CompanySetting::query()->create([
            'user_id' => $this->tenant->id,
            'default_vat_percent' => 15,
        ]);

        $catalog = app(ClinicServiceCatalogService::class);
        $catalog->seedDefaults((int) $this->tenant->id);

        $service = \App\Models\Clinic\ClinicService::query()
            ->where('user_id', $this->tenant->id)
            ->where('code', 'CONSULT')
            ->firstOrFail();

        $quote = app(ClinicBillingService::class)->quote((int) $this->tenant->id, [(int) $service->id]);

        $this->assertEqualsWithDelta(300.0, $quote['grand_total'], 0.01);
        $this->assertGreaterThan(0, $quote['vat_total']);
    }
}
