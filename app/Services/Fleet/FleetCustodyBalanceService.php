<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetCustodyReturn;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FleetCustodyBalanceService
{
    /**
     * @return list<array{
     *   agent_id: int,
     *   agent_name: string,
     *   sku_count: int,
     *   total_qty: float,
     *   total_value: float
     * }>
     */
    public function summaryByAgent(int $tenantUserId): array
    {
        $netByAgent = $this->netQuantitiesGroupedByAgent($tenantUserId);

        if ($netByAgent === []) {
            return [];
        }

        $agentIds = array_keys($netByAgent);
        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->whereIn('id', $agentIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = [];
        foreach ($netByAgent as $agentId => $products) {
            $positiveProducts = array_filter($products, fn (array $p): bool => $p['quantity'] > 0);
            if ($positiveProducts === []) {
                continue;
            }

            $rows[] = [
                'agent_id' => $agentId,
                'agent_name' => (string) ($agents[$agentId]->name ?? '—'),
                'sku_count' => count($positiveProducts),
                'total_qty' => round(array_sum(array_column($positiveProducts, 'quantity')), 4),
                'total_value' => round(array_sum(array_column($positiveProducts, 'line_value')), 4),
            ];
        }

        usort($rows, fn (array $a, array $b): int => strcmp($a['agent_name'], $b['agent_name']));

        return $rows;
    }

    /**
     * @return Collection<int, object{
     *   product_id: int,
     *   product_name: string,
     *   sku: ?string,
     *   quantity: float,
     *   unit_price: float,
     *   line_value: float
     * }>
     */
    public function linesForAgent(int $tenantUserId, int $agentId): Collection
    {
        $net = $this->netQuantitiesForAgent($tenantUserId, $agentId);

        if ($net === []) {
            return collect();
        }

        $productIds = array_keys($net);
        $products = DB::table('fleet_products')
            ->where('user_id', $tenantUserId)
            ->whereIn('id', $productIds)
            ->get(['id', 'name', 'sku'])
            ->keyBy('id');

        $rows = [];
        foreach ($net as $productId => $data) {
            if ($data['quantity'] <= 0) {
                continue;
            }

            $product = $products[$productId] ?? null;
            $rows[] = (object) [
                'product_id' => $productId,
                'product_name' => (string) ($product->name ?? '—'),
                'sku' => $product->sku ?? null,
                'quantity' => round($data['quantity'], 4),
                'unit_price' => round($data['unit_price'], 4),
                'line_value' => round($data['line_value'], 4),
            ];
        }

        usort($rows, fn ($a, $b): int => strcmp($a->product_name, $b->product_name));

        return collect($rows);
    }

    public function availableQuantity(int $tenantUserId, int $agentId, int $productId): float
    {
        $net = $this->netQuantitiesForAgent($tenantUserId, $agentId);

        return round((float) ($net[$productId]['quantity'] ?? 0), 4);
    }

    public function agent(int $tenantUserId, int $agentId): ?FleetAgent
    {
        return FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('id', $agentId)
            ->first(['id', 'name', 'phone']);
    }

    public function issuedIssuesCount(int $tenantUserId): int
    {
        return (int) FleetCustodyIssue::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCustodyIssue::STATUS_ISSUED)
            ->count();
    }

    public function agentsWithCustodyCount(int $tenantUserId): int
    {
        return count($this->summaryByAgent($tenantUserId));
    }

    public function confirmedReturnsCount(int $tenantUserId): int
    {
        return (int) FleetCustodyReturn::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCustodyReturn::STATUS_CONFIRMED)
            ->count();
    }

    /**
     * @return array<int, array{quantity: float, unit_price: float, line_value: float}>
     */
    private function netQuantitiesForAgent(int $tenantUserId, int $agentId): array
    {
        $issued = $this->issuedQuantitiesByProduct($tenantUserId, $agentId);
        $returned = $this->returnedQuantitiesByProduct($tenantUserId, $agentId);
        $sold = $this->soldQuantitiesByProduct($tenantUserId, $agentId);

        return $this->mergeNetQuantities($issued, $returned, $sold);
    }

    /**
     * @return array<int, array<int, array{quantity: float, unit_price: float, line_value: float}>>
     */
    private function netQuantitiesGroupedByAgent(int $tenantUserId): array
    {
        $issued = $this->movementQuantitiesByAgentProduct($tenantUserId, 'issue');
        $returned = $this->movementQuantitiesByAgentProduct($tenantUserId, 'return');
        $sold = $this->movementQuantitiesByAgentProduct($tenantUserId, 'sale');

        $agentIds = array_unique(array_merge(
            array_keys($issued),
            array_keys($returned),
            array_keys($sold),
        ));

        $result = [];
        foreach ($agentIds as $agentId) {
            $net = $this->mergeNetQuantities(
                $issued[$agentId] ?? [],
                $returned[$agentId] ?? [],
                $sold[$agentId] ?? [],
            );

            if ($net !== []) {
                $result[$agentId] = $net;
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array{quantity: float, unit_price: float}>  $issued
     * @param  array<int, array{quantity: float, unit_price: float}>  $returned
     * @param  array<int, array{quantity: float, unit_price: float}>  $sold
     * @return array<int, array{quantity: float, unit_price: float, line_value: float}>
     */
    private function mergeNetQuantities(array $issued, array $returned, array $sold): array
    {
        $productIds = array_unique(array_merge(
            array_keys($issued),
            array_keys($returned),
            array_keys($sold),
        ));

        $net = [];
        foreach ($productIds as $productId) {
            $qty = round(
                (float) ($issued[$productId]['quantity'] ?? 0)
                - (float) ($returned[$productId]['quantity'] ?? 0)
                - (float) ($sold[$productId]['quantity'] ?? 0),
                4,
            );

            if ($qty <= 0) {
                continue;
            }

            $unitPrice = (float) (
                $issued[$productId]['unit_price']
                ?? $returned[$productId]['unit_price']
                ?? $sold[$productId]['unit_price']
                ?? 0
            );

            $net[$productId] = [
                'quantity' => $qty,
                'unit_price' => round($unitPrice, 4),
                'line_value' => round($qty * $unitPrice, 4),
            ];
        }

        return $net;
    }

    /**
     * @return array<int, array{quantity: float, unit_price: float}>
     */
    private function issuedQuantitiesByProduct(int $tenantUserId, int $agentId): array
    {
        return $this->movementQuantitiesByAgentProduct($tenantUserId, 'issue')[$agentId] ?? [];
    }

    /**
     * @return array<int, array{quantity: float, unit_price: float}>
     */
    private function returnedQuantitiesByProduct(int $tenantUserId, int $agentId): array
    {
        return $this->movementQuantitiesByAgentProduct($tenantUserId, 'return')[$agentId] ?? [];
    }

    /**
     * @return array<int, array{quantity: float, unit_price: float}>
     */
    private function soldQuantitiesByProduct(int $tenantUserId, int $agentId): array
    {
        return $this->movementQuantitiesByAgentProduct($tenantUserId, 'sale')[$agentId] ?? [];
    }

    /**
     * @return array<int, array<int, array{quantity: float, unit_price: float}>>
     */
    private function movementQuantitiesByAgentProduct(int $tenantUserId, string $movement): array
    {
        $query = match ($movement) {
            'issue' => DB::table('fleet_custody_issue_lines as l')
                ->join('fleet_custody_issues as h', 'h.id', '=', 'l.issue_id')
                ->where('l.user_id', $tenantUserId)
                ->where('h.status', FleetCustodyIssue::STATUS_ISSUED)
                ->selectRaw('h.agent_id as agent_id')
                ->selectRaw('l.product_id as product_id')
                ->selectRaw('SUM(l.quantity) as quantity')
                ->selectRaw('MAX(l.unit_price) as unit_price')
                ->groupBy('h.agent_id', 'l.product_id'),
            'return' => DB::table('fleet_custody_return_lines as l')
                ->join('fleet_custody_returns as h', 'h.id', '=', 'l.return_id')
                ->where('l.user_id', $tenantUserId)
                ->where('h.status', FleetCustodyReturn::STATUS_CONFIRMED)
                ->selectRaw('h.agent_id as agent_id')
                ->selectRaw('l.product_id as product_id')
                ->selectRaw('SUM(l.quantity) as quantity')
                ->selectRaw('MAX(l.unit_price) as unit_price')
                ->groupBy('h.agent_id', 'l.product_id'),
            'sale' => DB::table('fleet_collection_lines as l')
                ->join('fleet_collections as h', 'h.id', '=', 'l.collection_id')
                ->where('l.user_id', $tenantUserId)
                ->where('h.status', FleetCollection::STATUS_CONFIRMED)
                ->selectRaw('h.agent_id as agent_id')
                ->selectRaw('l.product_id as product_id')
                ->selectRaw('SUM(l.quantity) as quantity')
                ->selectRaw('MAX(l.unit_price) as unit_price')
                ->groupBy('h.agent_id', 'l.product_id'),
            default => throw new \InvalidArgumentException('Unknown movement type.'),
        };

        $result = [];
        foreach ($query->get() as $row) {
            $agentId = (int) $row->agent_id;
            $productId = (int) $row->product_id;
            $result[$agentId][$productId] = [
                'quantity' => round((float) $row->quantity, 4),
                'unit_price' => round((float) $row->unit_price, 4),
            ];
        }

        return $result;
    }
}
