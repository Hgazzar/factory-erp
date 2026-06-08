<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetAgent;
use InvalidArgumentException;

final class FleetAgentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): FleetAgent
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('اسم المندوب مطلوب.');
        }

        return FleetAgent::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'phone' => $this->nullable($data['phone'] ?? null),
            'email' => $this->nullable($data['email'] ?? null),
            'status' => FleetAgent::STATUS_ACTIVE,
            'notes' => $this->nullable($data['notes'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FleetAgent $agent, int $tenantUserId, array $data): FleetAgent
    {
        if ((int) $agent->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('المندوب غير تابع لهذا الحساب.');
        }

        $name = trim((string) ($data['name'] ?? $agent->name));
        if ($name === '') {
            throw new InvalidArgumentException('اسم المندوب مطلوب.');
        }

        $status = (string) ($data['status'] ?? $agent->status);

        $agent->update([
            'name' => $name,
            'phone' => $this->nullable($data['phone'] ?? null),
            'email' => $this->nullable($data['email'] ?? null),
            'status' => in_array($status, [FleetAgent::STATUS_ACTIVE, FleetAgent::STATUS_INACTIVE], true)
                ? $status
                : $agent->status,
            'notes' => $this->nullable($data['notes'] ?? null),
        ]);

        return $agent->fresh();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
