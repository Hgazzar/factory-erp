<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildMedication;
use InvalidArgumentException;

final class NurseryChildMedicationService
{
    /**
     * @param  array<int, array<string, mixed>>|null  $rows
     */
    public function sync(Child $child, int $tenantUserId, ?array $rows): void
    {
        abort_unless((int) $child->user_id === $tenantUserId, 404);

        ChildMedication::query()
            ->where('user_id', $tenantUserId)
            ->where('child_id', $child->id)
            ->delete();

        if ($rows === null || $rows === []) {
            return;
        }

        $sort = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $frequency = trim((string) ($row['frequency'] ?? ''));
            if ($frequency !== '' && ! array_key_exists($frequency, ChildMedication::frequencyOptions())) {
                throw new InvalidArgumentException('تكرار الجرعة غير صالح.');
            }

            ChildMedication::query()->create([
                'user_id' => $tenantUserId,
                'child_id' => $child->id,
                'name' => $name,
                'dosage' => $this->nullable($row['dosage'] ?? null),
                'frequency' => $frequency !== '' ? $frequency : null,
                'schedule_notes' => $this->nullable($row['schedule_notes'] ?? null),
                'notes' => $this->nullable($row['notes'] ?? null),
                'sort_order' => $sort++,
            ]);
        }
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
