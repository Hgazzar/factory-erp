<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetProduct;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetCustodyTest extends FleetTestCase
{
    #[Test]
    public function admin_can_issue_custody_and_see_agent_balance(): void
    {
        $agent = FleetAgent::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'مندوب عهدة',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);

        $product = FleetProduct::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'صنف عهدة',
            'sku' => 'CUS-1',
            'sale_price' => 50,
            'is_active' => true,
        ]);

        $this->get(route('fleet.custody.index'))->assertOk()->assertSee('سندات العهدة');

        $this->post(route('fleet.custody.store'), [
            'agent_id' => $agent->id,
            'issued_on' => now()->toDateString(),
            'notes' => 'تسليم صباحي',
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ])->assertRedirect();

        $issue = FleetCustodyIssue::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($issue);
        $this->assertSame(FleetCustodyIssue::STATUS_DRAFT, $issue->status);

        $this->post(route('fleet.custody.confirm', $issue))->assertRedirect();
        $issue->refresh();
        $this->assertSame(FleetCustodyIssue::STATUS_ISSUED, $issue->status);

        $this->get(route('fleet.custody.balances'))
            ->assertOk()
            ->assertSee('مندوب عهدة');

        $this->get(route('fleet.custody.balances.agent', $agent))
            ->assertOk()
            ->assertSee('صنف عهدة')
            ->assertSee('10');

        $this->get(route('fleet.dashboard'))
            ->assertOk()
            ->assertSee('مناديب بعهدة');
    }
}
