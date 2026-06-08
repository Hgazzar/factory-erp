<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Models\Fleet\FleetRoute;
use App\Models\Fleet\FleetRouteStop;
use App\Services\Fleet\FleetRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetRouteWebController extends Controller
{
    use ResolvesOperationsTenant;

    /** @return list<array{value: string, label: string}> */
    private function statusLabels(): array
    {
        return [
            FleetRoute::STATUS_PLANNED => 'مجدول',
            FleetRoute::STATUS_IN_PROGRESS => 'جاري التنفيذ',
            FleetRoute::STATUS_COMPLETED => 'مكتمل',
            FleetRoute::STATUS_CANCELLED => 'ملغى',
        ];
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $date = trim((string) $request->query('date', now()->toDateString()));
        $agentId = (int) $request->query('agent_id', 0);

        $routes = FleetRoute::query()
            ->with(['agent:id,name', 'stops'])
            ->withCount([
                'stops',
                'stops as visited_stops_count' => fn ($q) => $q->where('status', FleetRouteStop::STATUS_VISITED),
            ])
            ->where('user_id', $tenantUserId)
            ->whereDate('route_date', $date)
            ->when($agentId > 0, fn ($q) => $q->where('agent_id', $agentId))
            ->orderBy('agent_id')
            ->get();

        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('fleet.routes.index', [
            'routes' => $routes,
            'agents' => $agents,
            'date' => $date,
            'agentId' => $agentId,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(): View
    {
        return view('fleet.routes.create', $this->formData($this->resolveOperationsTenantUserId()));
    }

    public function store(Request $request, FleetRouteService $routes): RedirectResponse
    {
        $data = $request->validate([
            'agent_id' => ['required', 'integer', 'min:1'],
            'route_date' => ['required', 'date'],
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $route = $routes->create(
                $this->resolveOperationsTenantUserId(),
                (int) $data['agent_id'],
                (string) $data['route_date'],
                array_map('intval', $data['customer_ids']),
                $data['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['route' => $e->getMessage()]);
        }

        return redirect()->route('fleet.routes.show', $route)->with('success', 'تم إنشاء خط السير.');
    }

    public function show(FleetRoute $route): View
    {
        $route->load([
            'agent:id,name,phone',
            'stops.customer:id,name,phone,city,address',
        ]);

        return view('fleet.routes.show', [
            'route' => $route,
            'statusLabels' => $this->statusLabels(),
            'stopStatusLabels' => [
                FleetRouteStop::STATUS_PENDING => 'بانتظار الزيارة',
                FleetRouteStop::STATUS_VISITED => 'تمت الزيارة',
                FleetRouteStop::STATUS_SKIPPED => 'تخطي',
            ],
        ]);
    }

    public function edit(FleetRoute $route): View
    {
        $route->load(['stops.customer:id,name']);

        return view('fleet.routes.edit', array_merge(
            $this->formData($this->resolveOperationsTenantUserId()),
            [
                'route' => $route,
                'selectedCustomerIds' => $route->stops->pluck('customer_id')->map(fn ($id) => (int) $id)->all(),
            ],
        ));
    }

    public function update(Request $request, FleetRoute $route, FleetRouteService $routes): RedirectResponse
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $routes->update(
                $route,
                $this->resolveOperationsTenantUserId(),
                array_map('intval', $data['customer_ids']),
                $data['notes'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['route' => $e->getMessage()]);
        }

        return redirect()->route('fleet.routes.show', $route)->with('success', 'تم تحديث خط السير.');
    }

    public function start(FleetRoute $route, FleetRouteService $routes): RedirectResponse
    {
        try {
            $routes->start($route, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['route' => $e->getMessage()]);
        }

        return back()->with('success', 'بدأ تنفيذ خط السير.');
    }

    public function complete(FleetRoute $route, FleetRouteService $routes): RedirectResponse
    {
        try {
            $routes->complete($route, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['route' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إغلاق خط السير.');
    }

    public function cancel(FleetRoute $route, FleetRouteService $routes): RedirectResponse
    {
        try {
            $routes->cancel($route, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['route' => $e->getMessage()]);
        }

        return redirect()->route('fleet.routes.index', ['date' => $route->route_date->toDateString()])
            ->with('success', 'تم إلغاء خط السير.');
    }

    public function updateStopStatus(Request $request, FleetRouteStop $stop, FleetRouteService $routes): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,visited,skipped'],
        ]);

        try {
            $routes->updateStopStatus($stop, $this->resolveOperationsTenantUserId(), $data['status']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['stop' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تحديث حالة الزيارة.');
    }

    /** @return array<string, mixed> */
    private function formData(int $tenantUserId): array
    {
        $customers = FleetCustomer::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetCustomer::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'assigned_agent_id']);

        return [
            'agents' => FleetAgent::query()
                ->where('user_id', $tenantUserId)
                ->where('status', FleetAgent::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name']),
            'customers' => $customers,
            'customerOptions' => $customers->map(fn (FleetCustomer $c): array => [
                'value' => (string) $c->id,
                'label' => trim($c->name.($c->city ? ' — '.$c->city : '')),
            ])->all(),
        ];
    }
}
