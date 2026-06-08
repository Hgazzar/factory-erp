<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Services\Fleet\FleetAgentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetAgentWebController extends Controller
{
    use ResolvesOperationsTenant;

    /** @return list<array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => FleetAgent::STATUS_ACTIVE, 'label' => 'نشط'],
            ['value' => FleetAgent::STATUS_INACTIVE, 'label' => 'غير نشط'],
        ];
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $q = trim((string) $request->query('q', ''));

        $base = FleetAgent::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', FleetAgent::STATUS_ACTIVE)->count(),
            'inactive' => (clone $base)->where('status', FleetAgent::STATUS_INACTIVE)->count(),
        ];

        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%');
            }))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('fleet.agents.index', compact('agents', 'q', 'listStats'));
    }

    public function create(): View
    {
        return view('fleet.agents.create', [
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request, FleetAgentService $agents): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $agents->create($this->resolveOperationsTenantUserId(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('fleet.agents.index')->with('success', 'تمت إضافة المندوب.');
    }

    public function edit(FleetAgent $agent): View
    {
        return view('fleet.agents.edit', [
            'agent' => $agent,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, FleetAgent $agent, FleetAgentService $agents): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'status' => ['required', Rule::in([FleetAgent::STATUS_ACTIVE, FleetAgent::STATUS_INACTIVE])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $agents->update($agent, $this->resolveOperationsTenantUserId(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('fleet.agents.index')->with('success', 'تم تحديث بيانات المندوب.');
    }
}
