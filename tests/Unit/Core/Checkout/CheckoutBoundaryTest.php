<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Checkout;

use App\Support\CheckoutBoundaryCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CheckoutBoundaryTest extends TestCase
{
    #[Test]
    public function clinic_and_nursery_modules_do_not_reference_store_payment_stack(): void
    {
        $forbidden = CheckoutBoundaryCatalog::forbiddenTokensInIsolatedModules();
        $violations = [];

        foreach (CheckoutBoundaryCatalog::ISOLATED_MODULE_PATH_PREFIXES as $prefix) {
            $directory = base_path($prefix);

            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if ($contents === false) {
                    continue;
                }

                foreach ($forbidden as $token) {
                    if (str_contains($contents, $token)) {
                        $violations[] = str_replace(base_path().'/', '', $file->getPathname())." → {$token}";
                    }
                }
            }
        }

        $this->assertSame([], $violations, 'Isolated modules must not duplicate Store checkout/payment: '.implode('; ', $violations));
    }

    #[Test]
    public function paymob_webhook_route_is_store_scoped_only(): void
    {
        $routes = collect(app('router')->getRoutes())->map(
            static fn ($route): string => $route->uri(),
        );

        $paymobRoutes = $routes->filter(
            static fn (string $uri): bool => str_contains($uri, 'paymob'),
        )->values()->all();

        $this->assertSame(['webhooks/store/paymob'], $paymobRoutes);
    }
}
