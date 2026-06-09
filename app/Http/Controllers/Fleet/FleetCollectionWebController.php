<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCollection;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetProduct;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use App\Services\Fleet\FleetCollectionService;
use App\Services\Fleet\FleetCustodyBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetCollectionWebController extends Controller
{
    use ResolvesOperationsTenant;

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            FleetCollection::STATUS_DRAFT => 'مسودة',
            FleetCollection::STATUS_CONFIRMED => 'مؤكد',
            FleetCollection::STATUS_VOID => 'ملغى',
        ];
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $agentId = (int) $request->query('agent_id', 0);
        $status = trim((string) $request->query('status', ''));
        $date = trim((string) $request->query('date', ''));

        $collections = FleetCollection::query()
            ->with(['agent:id,name', 'customer:id,name'])
            ->withCount('lines')
            ->where('user_id', $tenantUserId)
            ->when($agentId > 0, fn ($q) => $q->where('agent_id', $agentId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($date !== '', fn ($q) => $q->whereDate('collected_on', $date))
            ->orderByDesc('collected_on')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('fleet.collections.index', [
            'collections' => $collections,
            'agents' => $agents,
            'agentId' => $agentId,
            'status' => $status,
            'date' => $date,
            'statusLabels' => $this->statusLabels(),
            'paymentLabels' => FleetCollection::paymentMethodLabels(),
        ]);
    }

    public function create(Request $request, FleetCustodyBalanceService $balances): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $agentId = (int) $request->query('agent_id', 0);
        $customerId = (int) $request->query('customer_id', 0);
        $routeId = (int) $request->query('route_id', 0);
        $routeStopId = (int) $request->query('route_stop_id', 0);

        if ($routeStopId > 0) {
            $stop = FleetRouteStop::query()
                ->with('route:id,agent_id')
                ->where('user_id', $tenantUserId)
                ->where('id', $routeStopId)
                ->first();

            if ($stop !== null) {
                $routeId = (int) $stop->route_id;
                $agentId = (int) ($stop->route?->agent_id ?? $agentId);
                $customerId = (int) $stop->customer_id;
            }
        }

        return view('fleet.collections.create', array_merge(
            $this->formData($tenantUserId, $agentId),
            [
                'selectedAgentId' => $agentId > 0 ? (string) $agentId : old('agent_id', ''),
                'selectedCustomerId' => $customerId > 0 ? (string) $customerId : old('customer_id', ''),
                'selectedRouteId' => $routeId > 0 ? (string) $routeId : old('route_id', ''),
                'selectedRouteStopId' => $routeStopId > 0 ? (string) $routeStopId : old('route_stop_id', ''),
                'agentBalanceLines' => $agentId > 0
                    ? $balances->linesForAgent($tenantUserId, $agentId)
                    : collect(),
            ],
        ));
    }

    public function store(Request $request, FleetCollectionService $collections): RedirectResponse
    {
        $data = $request->validate([
            'agent_id' => ['required', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'route_id' => ['nullable', 'integer', 'min:1'],
            'route_stop_id' => ['nullable', 'integer', 'min:1'],
            'collected_on' => ['required', 'date'],
            'payment_method' => ['required', 'in:cod,transfer,credit'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $collection = $collections->createDraft(
                $this->resolveOperationsTenantUserId(),
                (int) $data['agent_id'],
                (string) $data['collected_on'],
                $data['lines'],
                [
                    'customer_id' => isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                    'route_id' => isset($data['route_id']) ? (int) $data['route_id'] : null,
                    'route_stop_id' => isset($data['route_stop_id']) ? (int) $data['route_stop_id'] : null,
                    'payment_method' => $data['payment_method'],
                ],
                $data['notes'] ?? null,
                auth()->id() ? (int) auth()->id() : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['collection' => $e->getMessage()]);
        }

        return redirect()->route('fleet.collections.show', $collection)->with('success', 'تم حفظ مسودة التحصيل.');
    }

    public function show(FleetCollection $collection): View
    {
        $collection->load([
            'agent:id,name,phone',
            'customer:id,name,phone',
            'route:id,route_date',
            'routeStop.customer:id,name',
            'lines.product:id,name,sku,sale_price',
        ]);

        return view('fleet.collections.show', [
            'collection' => $collection,
            'statusLabels' => $this->statusLabels(),
            'paymentLabels' => FleetCollection::paymentMethodLabels(),
            'totalQty' => round($collection->lines->sum('quantity'), 4),
        ]);
    }

    public function confirm(FleetCollection $collection, FleetCollectionService $service): RedirectResponse
    {
        try {
            $service->confirm($collection, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['collection' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تأكيد التحصيل وخصم العهدة.');
    }

    public function void(FleetCollection $collection, FleetCollectionService $service): RedirectResponse
    {
        try {
            $service->void($collection, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['collection' => $e->getMessage()]);
        }

        return redirect()->route('fleet.collections.index')->with('success', 'تم إلغاء سند التحصيل.');
    }

    /** @return array<string, mixed> */
    private function formData(int $tenantUserId, int $agentId = 0): array
    {
        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        $customers = FleetCustomer::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCustomer::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'city']);

        $products = FleetProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sale_price']);

        $routes = FleetRoute::query()
            ->where('user_id', $tenantUserId)
            ->when($agentId > 0, fn ($q) => $q->where('agent_id', $agentId))
            ->whereIn('status', [FleetRoute::STATUS_PLANNED, FleetRoute::STATUS_IN_PROGRESS])
            ->orderByDesc('route_date')
            ->limit(30)
            ->get(['id', 'agent_id', 'route_date']);

        return [
            'agents' => $agents,
            'agentOptions' => $agents->map(fn (FleetAgent $a): array => [
                'value' => (string) $a->id,
                'label' => $a->name,
            ])->all(),
            'customers' => $customers,
            'customerOptions' => $customers->map(fn (FleetCustomer $c): array => [
                'value' => (string) $c->id,
                'label' => trim($c->name.($c->city ? ' — '.$c->city : '')),
            ])->all(),
            'products' => $products,
            'productOptions' => $products->map(fn (FleetProduct $p): array => [
                'value' => (string) $p->id,
                'label' => trim($p->name.($p->sku ? " ({$p->sku})" : '')),
                'meta' => (string) $p->sale_price,
            ])->all(),
            'routes' => $routes,
            'routeOptions' => $routes->map(fn (FleetRoute $r): array => [
                'value' => (string) $r->id,
                'label' => $r->route_date->format('Y-m-d').' — خط #'.$r->id,
            ])->all(),
            'paymentOptions' => collect(FleetCollection::paymentMethodLabels())
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ];
    }
}
