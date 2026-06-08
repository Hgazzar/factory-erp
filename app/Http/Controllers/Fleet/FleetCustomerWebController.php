<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustomer;
use App\Services\Fleet\FleetCustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetCustomerWebController extends Controller
{
    use ResolvesOperationsTenant;

    /** @return list<array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => FleetCustomer::STATUS_ACTIVE, 'label' => 'نشط'],
            ['value' => FleetCustomer::STATUS_INACTIVE, 'label' => 'غير نشط'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function agentOptions(int $tenantUserId): array
    {
        return FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (FleetAgent $agent): array => [
                'value' => (string) $agent->id,
                'label' => $agent->name,
            ])
            ->all();
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $q = trim((string) $request->query('q', ''));
        $agentId = (int) $request->query('agent_id', 0);

        $base = FleetCustomer::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', FleetCustomer::STATUS_ACTIVE)->count(),
            'inactive' => (clone $base)->where('status', FleetCustomer::STATUS_INACTIVE)->count(),
        ];

        $customers = FleetCustomer::query()
            ->with('assignedAgent:id,name')
            ->where('user_id', $tenantUserId)
            ->when($agentId > 0, fn ($query) => $query->where('assigned_agent_id', $agentId))
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('city', 'like', '%'.$q.'%');
            }))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('fleet.customers.index', compact('customers', 'q', 'listStats', 'agents', 'agentId'));
    }

    public function create(): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return view('fleet.customers.create', [
            'statusOptions' => $this->statusOptions(),
            'agentOptions' => $this->agentOptions($tenantUserId),
        ]);
    }

    public function store(Request $request, FleetCustomerService $customers): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:64'],
            'assigned_agent_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $customers->create($this->resolveOperationsTenantUserId(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('fleet.customers.index')->with('success', 'تمت إضافة العميل.');
    }

    public function edit(FleetCustomer $customer): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return view('fleet.customers.edit', [
            'customer' => $customer,
            'statusOptions' => $this->statusOptions(),
            'agentOptions' => $this->agentOptions($tenantUserId),
        ]);
    }

    public function update(Request $request, FleetCustomer $customer, FleetCustomerService $customers): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:64'],
            'assigned_agent_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in([FleetCustomer::STATUS_ACTIVE, FleetCustomer::STATUS_INACTIVE])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $customers->update($customer, $this->resolveOperationsTenantUserId(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('fleet.customers.index')->with('success', 'تم تحديث بيانات العميل.');
    }
}
