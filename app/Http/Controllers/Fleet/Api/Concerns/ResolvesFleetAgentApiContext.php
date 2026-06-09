<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet\Api\Concerns;

use App\Models\Fleet\FleetAgent;
use Illuminate\Http\Request;

trait ResolvesFleetAgentApiContext
{
    protected function tenantUserId(Request $request): int
    {
        return (int) $request->attributes->get('fleet_agent_tenant_user_id');
    }

    protected function agent(Request $request): FleetAgent
    {
        /** @var FleetAgent $agent */
        $agent = $request->attributes->get('fleet_agent');

        return $agent;
    }
}
