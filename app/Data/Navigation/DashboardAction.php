<?php

declare(strict_types=1);

namespace App\Data\Navigation;

final class DashboardAction
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $route,
        public readonly ?string $icon = null,
    ) {}

    public function url(): string
    {
        return route($this->route);
    }
}
