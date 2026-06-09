<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetCustodyIssueLine;
use App\Models\Fleet\FleetProduct;
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
        $rows = DB::table('fleet_custody_issue_lines as l')
            ->join('fleet_custody_issues as i', 'i.id', '=', 'l.issue_id')
            ->join('fleet_agents as a', 'a.id', '=', 'i.agent_id')
            ->where('l.user_id', $tenantUserId)
            ->where('i.status', FleetCustodyIssue::STATUS_ISSUED)
            ->groupBy('i.agent_id', 'a.name')
            ->selectRaw('i.agent_id as agent_id')
            ->selectRaw('a.name as agent_name')
            ->selectRaw('COUNT(DISTINCT l.product_id) as sku_count')
            ->selectRaw('SUM(l.quantity) as total_qty')
            ->selectRaw('SUM(l.quantity * l.unit_price) as total_value')
            ->orderBy('a.name')
            ->get();

        return $rows->map(fn ($row): array => [
            'agent_id' => (int) $row->agent_id,
            'agent_name' => (string) $row->agent_name,
            'sku_count' => (int) $row->sku_count,
            'total_qty' => round((float) $row->total_qty, 4),
            'total_value' => round((float) $row->total_value, 4),
        ])->all();
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
        return DB::table('fleet_custody_issue_lines as l')
            ->join('fleet_custody_issues as i', 'i.id', '=', 'l.issue_id')
            ->join('fleet_products as p', 'p.id', '=', 'l.product_id')
            ->where('l.user_id', $tenantUserId)
            ->where('i.agent_id', $agentId)
            ->where('i.status', FleetCustodyIssue::STATUS_ISSUED)
            ->groupBy('l.product_id', 'p.name', 'p.sku', 'l.unit_price')
            ->selectRaw('l.product_id as product_id')
            ->selectRaw('p.name as product_name')
            ->selectRaw('p.sku as sku')
            ->selectRaw('SUM(l.quantity) as quantity')
            ->selectRaw('l.unit_price as unit_price')
            ->selectRaw('SUM(l.quantity * l.unit_price) as line_value')
            ->orderBy('p.name')
            ->get()
            ->map(function ($row): object {
                $row->quantity = round((float) $row->quantity, 4);
                $row->unit_price = round((float) $row->unit_price, 4);
                $row->line_value = round((float) $row->line_value, 4);

                return $row;
            });
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
        return (int) FleetCustodyIssue::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCustodyIssue::STATUS_ISSUED)
            ->distinct()
            ->count('agent_id');
    }
}
