<?php

declare(strict_types=1);

namespace App\Data\Navigation;

final class NavigationLink
{
    public function __construct(
        public readonly string $key,
        public readonly string $route,
        public readonly string $label,
        public readonly string $shell,
        public readonly ?string $module = null,
        public readonly string|array|null $activePattern = null,
        public readonly ?string $group = null,
        public readonly ?string $hint = null,
        public readonly ?string $infoField = null,
    ) {}

    public function url(): string
    {
        return route($this->route);
    }

    public function isActive(): bool
    {
        $patterns = $this->activePattern ?? $this->route;

        return request()->routeIs($patterns);
    }

    /**
     * @return array{key: string, label: string, route: string, hint: ?string, shell: string, module: ?string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'route' => $this->route,
            'hint' => $this->hint,
            'shell' => $this->shell,
            'module' => $this->module,
        ];
    }
}
