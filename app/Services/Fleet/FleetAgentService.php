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

        $phone = $this->nullable($data['phone'] ?? null);
        $apiPin = trim((string) ($data['api_pin'] ?? ''));

        if ($apiPin !== '' && $phone === null) {
            throw new InvalidArgumentException('رقم الجوال مطلوب عند تفعيل دخول تطبيق المندوب.');
        }

        $agent = FleetAgent::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'phone' => $phone,
            'email' => $this->nullable($data['email'] ?? null),
            'status' => FleetAgent::STATUS_ACTIVE,
            'notes' => $this->nullable($data['notes'] ?? null),
        ]);

        if ($apiPin !== '') {
            $this->applyApiPin($agent, $apiPin);
        }

        return $agent->fresh();
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

        $phone = $this->nullable($data['phone'] ?? null);
        $apiPin = trim((string) ($data['api_pin'] ?? ''));

        if ($apiPin !== '' && $phone === null) {
            throw new InvalidArgumentException('رقم الجوال مطلوب عند تفعيل دخول تطبيق المندوب.');
        }

        $agent->update([
            'name' => $name,
            'phone' => $phone,
            'email' => $this->nullable($data['email'] ?? null),
            'status' => in_array($status, [FleetAgent::STATUS_ACTIVE, FleetAgent::STATUS_INACTIVE], true)
                ? $status
                : $agent->status,
            'notes' => $this->nullable($data['notes'] ?? null),
        ]);

        if ($apiPin !== '') {
            $this->applyApiPin($agent, $apiPin);
        }

        return $agent->fresh();
    }

    private function applyApiPin(FleetAgent $agent, string $pin): void
    {
        $min = max(4, (int) config('fleet.agent_api.pin_min_length', 4));
        $max = max($min, (int) config('fleet.agent_api.pin_max_length', 8));

        if (! preg_match('/^\d+$/', $pin) || strlen($pin) < $min || strlen($pin) > $max) {
            throw new InvalidArgumentException("رمز دخول التطبيق يجب أن يكون {$min}–{$max} أرقام.");
        }

        $agent->setApiPin($pin);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
