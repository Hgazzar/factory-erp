<?php

declare(strict_types=1);

namespace App\Contracts\Core\Metrics;

interface MetricsQueryInterface
{
    public function key(): string;

    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $tenantUserId): array;
}
