<?php

declare(strict_types=1);

namespace Tests\Feature\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetCustodyReturn;
use App\Models\Fleet\FleetProduct;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FleetTestCase;

final class FleetCustodyReturnTest extends FleetTestCase
{
    #[Test]
    public function admin_can_return_custody_and_reduce_agent_balance(): void
    {
        [$agent, $product] = $this->seedAgentWithCustody(issueQty: 10);

        $this->get(route('fleet.custody.returns.index'))->assertOk()->assertSee('مرتجعات العهدة');

        $this->post(route('fleet.custody.returns.store'), [
            'agent_id' => $agent->id,
            'returned_on' => now()->toDateString(),
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 4],
            ],
        ])->assertRedirect();

        $return = FleetCustodyReturn::query()->where('user_id', $this->tenant->id)->first();
        $this->assertNotNull($return);

        $this->post(route('fleet.custody.returns.confirm', $return))->assertRedirect();
        $return->refresh();
        $this->assertSame(FleetCustodyReturn::STATUS_CONFIRMED, $return->status);

        $this->get(route('fleet.custody.balances.agent', $agent))
            ->assertOk()
            ->assertSee('6');
    }

    /** @return array{0: FleetAgent, 1: FleetProduct} */
    private function seedAgentWithCustody(float $issueQty): array
    {
        $agent = FleetAgent::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'مندوب رصيد',
            'status' => FleetAgent::STATUS_ACTIVE,
        ]);

        $product = FleetProduct::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'صنف متداول',
            'sku' => 'FL-1',
            'sale_price' => 25,
            'is_active' => true,
        ]);

        $this->post(route('fleet.custody.store'), [
            'agent_id' => $agent->id,
            'issued_on' => now()->toDateString(),
            'lines' => [
                ['product_id' => $product->id, 'quantity' => $issueQty],
            ],
        ])->assertRedirect();

        $issue = FleetCustodyIssue::query()->where('user_id', $this->tenant->id)->first();
        $this->post(route('fleet.custody.confirm', $issue))->assertRedirect();

        return [$agent, $product];
    }
}
