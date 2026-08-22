<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NavigationLinksIntegrityTest extends TestCase
{
    #[Test]
    public function every_navigation_link_route_is_registered(): void
    {
        $missing = [];

        foreach (config('navigation.links', []) as $linkKey => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $route = (string) ($definition['route'] ?? '');
            if ($route === '') {
                $missing[] = "{$linkKey}: empty route";

                continue;
            }

            if (! Route::has($route)) {
                $missing[] = "{$linkKey}: {$route}";
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Navigation links reference missing routes:\n".implode("\n", $missing)
        );
    }

    #[Test]
    public function every_launcher_route_is_registered(): void
    {
        $missing = [];

        foreach (config('navigation.launchers', []) as $launcherKey => $launcher) {
            if (! is_array($launcher)) {
                continue;
            }

            $route = (string) ($launcher['route'] ?? '');
            if ($route === '' || Route::has($route)) {
                continue;
            }

            $missing[] = "{$launcherKey}: {$route}";
        }

        $this->assertSame([], $missing);
    }

    #[Test]
    public function surface_link_keys_exist_in_links_registry(): void
    {
        $linkRegistry = config('navigation.links', []);
        $orphans = [];

        foreach (config('navigation.surfaces', []) as $surfaceKey => $keys) {
            if (! is_array($keys)) {
                continue;
            }

            foreach ($keys as $linkKey) {
                if (! is_string($linkKey) || $linkKey === '') {
                    continue;
                }

                if (! isset($linkRegistry[$linkKey])) {
                    $orphans[] = "{$surfaceKey} → {$linkKey}";
                }
            }
        }

        $this->assertSame([], $orphans, 'Surface references unknown link keys');
    }

    #[Test]
    public function dashboard_action_keys_exist_in_links_registry(): void
    {
        $linkRegistry = config('navigation.links', []);
        $orphans = [];

        foreach (config('navigation.dashboard_actions', []) as $module => $keys) {
            if (! is_array($keys)) {
                continue;
            }

            foreach ($keys as $linkKey) {
                if (! is_string($linkKey) || $linkKey === '') {
                    continue;
                }

                if (! isset($linkRegistry[$linkKey])) {
                    $orphans[] = "{$module} → {$linkKey}";
                }
            }
        }

        $this->assertSame([], $orphans, 'Dashboard actions reference unknown link keys');
    }
}
