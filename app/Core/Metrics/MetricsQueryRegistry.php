<?php

declare(strict_types=1);

namespace App\Core\Metrics;

use App\Contracts\Core\Metrics\MetricsQueryInterface;
use InvalidArgumentException;

final class MetricsQueryRegistry
{
    /** @var array<string, MetricsQueryInterface> */
    private array $queries = [];

    public function register(MetricsQueryInterface $query): void
    {
        $this->queries[$query->key()] = $query;
    }

    public function get(string $key): MetricsQueryInterface
    {
        $key = strtolower(trim($key));

        if (! isset($this->queries[$key])) {
            throw new InvalidArgumentException("Metrics query «{$key}» غير مسجّل.");
        }

        return $this->queries[$key];
    }

    /**
     * @return list<MetricsQueryInterface>
     */
    public function all(): array
    {
        return array_values($this->queries);
    }
}
