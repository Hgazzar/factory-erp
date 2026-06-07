<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Nursery\NurseryShift;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class NurseryShiftService
{
    /**
     * @return Collection<int, NurseryShift>
     */
    public function listForTenant(int $tenantUserId): Collection
    {
        return NurseryShift::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  list<array{name?: string, start_time?: string, end_time?: string}>  $rows
     * @return list<NurseryShift>
     */
    public function createBatch(int $tenantUserId, array $rows): array
    {
        $created = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $start = trim((string) ($row['start_time'] ?? ''));
            $end = trim((string) ($row['end_time'] ?? ''));

            if ($name === '' && $start === '' && $end === '') {
                continue;
            }

            $created[] = $this->createOne($tenantUserId, $name, $start, $end);
        }

        if ($created === []) {
            throw new InvalidArgumentException('أضف مناوبة واحدة على الأقل.');
        }

        return $created;
    }

    public function update(NurseryShift $shift, int $tenantUserId, array $data): NurseryShift
    {
        abort_unless((int) $shift->user_id === $tenantUserId, 404);

        $name = trim((string) ($data['name'] ?? $shift->name));
        $start = trim((string) ($data['start_time'] ?? ''));
        $end = trim((string) ($data['end_time'] ?? ''));

        if ($start === '' || $end === '') {
            throw new InvalidArgumentException('وقت البداية والنهاية مطلوبان.');
        }

        $shift->update([
            'name' => $name !== '' ? $name : 'مناوبة',
            'start_time' => $this->normalizeTime($start),
            'end_time' => $this->normalizeTime($end),
        ]);

        return $shift->fresh();
    }

    public function deactivate(NurseryShift $shift, int $tenantUserId): void
    {
        abort_unless((int) $shift->user_id === $tenantUserId, 404);
        $shift->update(['is_active' => false]);
    }

    private function createOne(int $tenantUserId, string $name, string $start, string $end): NurseryShift
    {
        if ($name === '') {
            throw new InvalidArgumentException('اسم المناوبة مطلوب.');
        }

        if ($start === '' || $end === '') {
            throw new InvalidArgumentException('وقت البداية والنهاية مطلوبان.');
        }

        return NurseryShift::query()->create([
            'user_id' => $tenantUserId,
            'name' => $name,
            'start_time' => $this->normalizeTime($start),
            'end_time' => $this->normalizeTime($end),
            'is_active' => true,
        ]);
    }

    private function normalizeTime(string $time): string
    {
        if (! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            throw new InvalidArgumentException('صيغة الوقت غير صالحة (HH:MM).');
        }

        [$h, $m] = array_map('intval', explode(':', $time));
        if ($h < 0 || $h > 23 || $m < 0 || $m > 59) {
            throw new InvalidArgumentException('وقت غير صالح.');
        }

        return sprintf('%02d:%02d:00', $h, $m);
    }
}
