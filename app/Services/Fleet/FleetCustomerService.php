<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\FleetCustomer;
use InvalidArgumentException;

final class FleetCustomerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantUserId, array $data): FleetCustomer
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('اسم العميل مطلوب.');
        }

        return FleetCustomer::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'phone' => $this->nullable($data['phone'] ?? null),
            'email' => $this->nullable($data['email'] ?? null),
            'address' => $this->nullable($data['address'] ?? null),
            'city' => $this->nullable($data['city'] ?? null),
            'region' => $this->nullable($data['region'] ?? null),
            'assigned_agent_id' => isset($data['assigned_agent_id']) && (int) $data['assigned_agent_id'] > 0
                ? (int) $data['assigned_agent_id']
                : null,
            'status' => FleetCustomer::STATUS_ACTIVE,
            'notes' => $this->nullable($data['notes'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FleetCustomer $customer, int $tenantUserId, array $data): FleetCustomer
    {
        if ((int) $customer->user_id !== $tenantUserId) {
            throw new InvalidArgumentException('العميل غير تابع لهذا الحساب.');
        }

        $name = trim((string) ($data['name'] ?? $customer->name));
        if ($name === '') {
            throw new InvalidArgumentException('اسم العميل مطلوب.');
        }

        $status = (string) ($data['status'] ?? $customer->status);

        $customer->update([
            'name' => $name,
            'phone' => $this->nullable($data['phone'] ?? null),
            'email' => $this->nullable($data['email'] ?? null),
            'address' => $this->nullable($data['address'] ?? null),
            'city' => $this->nullable($data['city'] ?? null),
            'region' => $this->nullable($data['region'] ?? null),
            'assigned_agent_id' => isset($data['assigned_agent_id']) && (int) $data['assigned_agent_id'] > 0
                ? (int) $data['assigned_agent_id']
                : null,
            'status' => in_array($status, [FleetCustomer::STATUS_ACTIVE, FleetCustomer::STATUS_INACTIVE], true)
                ? $status
                : $customer->status,
            'notes' => $this->nullable($data['notes'] ?? null),
        ]);

        return $customer->fresh();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
