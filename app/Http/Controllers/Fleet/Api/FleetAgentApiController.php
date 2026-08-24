<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet\Api;

use App\Data\Fleet\GeoCapture;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Fleet\Api\Concerns\ResolvesFleetAgentApiContext;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetRouteStop;
use App\Models\Fleet\FleetVisit;
use App\Services\Fleet\FleetAgentAuthService;
use App\Services\Fleet\FleetAgentMobileService;
use App\Services\Fleet\FleetCollectionService;
use App\Services\Fleet\FleetRouteService;
use App\Services\Fleet\FleetVisitService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class FleetAgentApiController extends Controller
{
    use ResolvesFleetAgentApiContext;

    public function me(Request $request, FleetAgentMobileService $mobile, FleetAgentAuthService $auth): JsonResponse
    {
        $agent = $this->agent($request);
        $tenantUserId = $this->tenantUserId($request);

        return ApiResponse::success([
            'agent' => $auth->agentPayload($agent),
            'dashboard' => $mobile->dashboard($tenantUserId, $agent),
        ]);
    }

    public function routes(Request $request, FleetAgentMobileService $mobile): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $routes = $mobile->routesForAgent(
            $this->tenantUserId($request),
            $this->agent($request),
            $validated['date'] ?? null,
        );

        return ApiResponse::success([
            'routes' => $routes->map(fn ($route) => $mobile->routePayload($route))->values()->all(),
        ]);
    }

    public function showRoute(Request $request, int $route, FleetAgentMobileService $mobile): JsonResponse
    {
        try {
            $model = $mobile->routeForAgent($this->tenantUserId($request), $this->agent($request), $route);

            return ApiResponse::success(['route' => $mobile->routeDetailPayload($model)]);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 404, 'route_not_found');
        }
    }

    public function startRoute(Request $request, int $route, FleetRouteService $routes, FleetAgentMobileService $mobile): JsonResponse
    {
        try {
            $model = $mobile->routeForAgent($this->tenantUserId($request), $this->agent($request), $route);
            $started = $routes->start($model, $this->tenantUserId($request));

            return ApiResponse::success([
                'route' => $mobile->routeDetailPayload(
                    $started->load(['stops.customer:id,name,phone,address,city', 'stops.posSale:id,invoice_number,total_amount']),
                ),
            ]);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'route_start_failed');
        }
    }

    public function updateStopStatus(Request $request, int $stop, FleetRouteService $routes, FleetAgentMobileService $mobile, FleetVisitService $visits): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,visited,skipped'],
            'outcome' => ['nullable', 'string', 'in:sale,no_sale,skipped'],
            'visit_reason' => ['nullable', 'string', 'max:191'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'is_mocked' => ['nullable', 'boolean'],
        ]);

        $tenantUserId = $this->tenantUserId($request);
        $agent = $this->agent($request);
        $outcome = $this->resolveStopOutcome($validated['status'], $validated['outcome'] ?? null);

        if ($outcome === FleetVisit::OUTCOME_NO_SALE && trim((string) ($validated['visit_reason'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'visit_reason' => 'سبب عدم البيع مطلوب عند إغلاق الزيارة بدون فاتورة.',
            ]);
        }

        try {
            $model = $mobile->stopForAgent($tenantUserId, $agent, $stop);
            $updated = $routes->updateStopStatus($model, $tenantUserId, $validated['status']);

            if (in_array($validated['status'], [FleetRouteStop::STATUS_VISITED, FleetRouteStop::STATUS_SKIPPED], true)) {
                $visits->recordForStop(
                    $tenantUserId,
                    (int) $agent->id,
                    $updated,
                    $outcome,
                    $validated['visit_reason'] ?? null,
                    GeoCapture::fromRequest($validated),
                );
            }

            return ApiResponse::success(['stop' => $mobile->stopPayload($updated)]);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'stop_update_failed');
        }
    }

    private function resolveStopOutcome(string $status, ?string $outcome): string
    {
        if ($status === FleetRouteStop::STATUS_SKIPPED) {
            return FleetVisit::OUTCOME_SKIPPED;
        }

        if ($outcome === FleetVisit::OUTCOME_NO_SALE) {
            return FleetVisit::OUTCOME_NO_SALE;
        }

        return FleetVisit::OUTCOME_SALE;
    }

    public function custodyBalance(Request $request, FleetAgentMobileService $mobile): JsonResponse
    {
        $lines = $mobile->custodyBalance($this->tenantUserId($request), $this->agent($request));

        return ApiResponse::success([
            'lines' => $lines->map(static fn ($line): array => [
                'product_id' => (int) $line->product_id,
                'product_name' => $line->product_name,
                'sku' => $line->sku,
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'line_value' => (float) $line->line_value,
            ])->values()->all(),
        ]);
    }

    public function products(FleetAgentMobileService $mobile, Request $request): JsonResponse
    {
        $products = $mobile->activeProducts($this->tenantUserId($request));

        return ApiResponse::success([
            'products' => $products->map(static fn ($p): array => [
                'id' => (int) $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'sale_price' => (float) $p->sale_price,
                'image_url' => $p->image_url,
            ])->values()->all(),
        ]);
    }

    public function collections(Request $request, FleetAgentMobileService $mobile): JsonResponse
    {
        $items = $mobile->recentCollections($this->tenantUserId($request), $this->agent($request));

        return ApiResponse::success([
            'collections' => $items->map(fn ($c) => $mobile->collectionPayload($c))->values()->all(),
        ]);
    }

    public function showCollection(Request $request, int $collection, FleetAgentMobileService $mobile): JsonResponse
    {
        try {
            $model = $mobile->collectionForAgent($this->tenantUserId($request), $this->agent($request), $collection);

            return ApiResponse::success(['collection' => $mobile->collectionPayload($model, withLines: true)]);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 404, 'collection_not_found');
        }
    }

    public function storeCollection(Request $request, FleetCollectionService $collections, FleetAgentMobileService $mobile, FleetVisitService $visits): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'route_id' => ['nullable', 'integer', 'min:1'],
            'route_stop_id' => ['nullable', 'integer', 'min:1'],
            'collected_on' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', 'in:cod,transfer,credit'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'is_mocked' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $agent = $this->agent($request);
        $tenantUserId = $this->tenantUserId($request);

        $lines = array_map(static fn (array $line): array => [
            'product_id' => (int) $line['product_id'],
            'quantity' => (float) $line['quantity'],
            'unit_price' => isset($line['unit_price']) ? (float) $line['unit_price'] : null,
        ], $validated['lines']);

        try {
            $collection = $collections->createDraft(
                $tenantUserId,
                (int) $agent->id,
                $validated['collected_on'] ?? now()->toDateString(),
                $lines,
                [
                    'customer_id' => $validated['customer_id'] ?? null,
                    'route_id' => $validated['route_id'] ?? null,
                    'route_stop_id' => $validated['route_stop_id'] ?? null,
                    'payment_method' => $validated['payment_method'] ?? FleetCollection::PAYMENT_COD,
                ],
                $validated['notes'] ?? null,
                (int) $agent->id,
            );

            $visits->recordForCollection(
                $tenantUserId,
                (int) $agent->id,
                $collection,
                GeoCapture::fromRequest($validated),
            );

            return ApiResponse::success(
                ['collection' => $mobile->collectionPayload($collection->load('lines.product'), withLines: true)],
                201,
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'collection_create_failed');
        }
    }

    public function confirmCollection(Request $request, int $collection, FleetCollectionService $collections, FleetAgentMobileService $mobile): JsonResponse
    {
        try {
            $model = $mobile->collectionForAgent($this->tenantUserId($request), $this->agent($request), $collection);
            $confirmed = $collections->confirm($model, $this->tenantUserId($request));

            return ApiResponse::success(['collection' => $mobile->collectionPayload($confirmed, withLines: true)]);
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422, 'collection_confirm_failed');
        }
    }
}
