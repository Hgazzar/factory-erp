<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyReturn;
use App\Models\Fleet\FleetProduct;
use App\Services\Fleet\FleetCustodyBalanceService;
use App\Services\Fleet\FleetCustodyReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetCustodyReturnWebController extends Controller
{
    use ResolvesOperationsTenant;

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            FleetCustodyReturn::STATUS_DRAFT => 'مسودة',
            FleetCustodyReturn::STATUS_CONFIRMED => 'مؤكد',
            FleetCustodyReturn::STATUS_VOID => 'ملغى',
        ];
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $agentId = (int) $request->query('agent_id', 0);
        $status = trim((string) $request->query('status', ''));

        $returns = FleetCustodyReturn::query()
            ->with(['agent:id,name'])
            ->withCount('lines')
            ->where('user_id', $tenantUserId)
            ->when($agentId > 0, fn ($q) => $q->where('agent_id', $agentId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('returned_on')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('fleet.custody.returns.index', [
            'returns' => $returns,
            'agents' => $agents,
            'agentId' => $agentId,
            'status' => $status,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(Request $request, FleetCustodyBalanceService $balances): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $agentId = (int) $request->query('agent_id', 0);

        return view('fleet.custody.returns.create', array_merge(
            $this->formData($tenantUserId),
            [
                'selectedAgentId' => $agentId > 0 ? (string) $agentId : old('agent_id', ''),
                'agentBalanceLines' => $agentId > 0
                    ? $balances->linesForAgent($tenantUserId, $agentId)
                    : collect(),
            ],
        ));
    }

    public function store(Request $request, FleetCustodyReturnService $returns): RedirectResponse
    {
        $data = $request->validate([
            'agent_id' => ['required', 'integer', 'min:1'],
            'returned_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $return = $returns->createDraft(
                $this->resolveOperationsTenantUserId(),
                (int) $data['agent_id'],
                (string) $data['returned_on'],
                $data['lines'],
                $data['notes'] ?? null,
                auth()->id() ? (int) auth()->id() : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['custody_return' => $e->getMessage()]);
        }

        return redirect()->route('fleet.custody.returns.show', $return)->with('success', 'تم حفظ مسودة المرتجع.');
    }

    public function show(FleetCustodyReturn $custodyReturn): View
    {
        $custodyReturn->load(['agent:id,name,phone', 'lines.product:id,name,sku,sale_price']);

        return view('fleet.custody.returns.show', [
            'return' => $custodyReturn,
            'statusLabels' => $this->statusLabels(),
            'totalQty' => round($custodyReturn->lines->sum('quantity'), 4),
            'totalValue' => round($custodyReturn->lines->sum(fn ($l) => (float) $l->quantity * (float) $l->unit_price), 4),
        ]);
    }

    public function confirm(FleetCustodyReturn $custodyReturn, FleetCustodyReturnService $service): RedirectResponse
    {
        try {
            $service->confirm($custodyReturn, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['custody_return' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تأكيد مرتجع العهدة.');
    }

    public function void(FleetCustodyReturn $custodyReturn, FleetCustodyReturnService $service): RedirectResponse
    {
        try {
            $service->void($custodyReturn, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['custody_return' => $e->getMessage()]);
        }

        return redirect()->route('fleet.custody.returns.index')->with('success', 'تم إلغاء سند المرتجع.');
    }

    /** @return array<string, mixed> */
    private function formData(int $tenantUserId): array
    {
        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'agents' => $agents,
            'agentOptions' => $agents->map(fn (FleetAgent $a): array => [
                'value' => (string) $a->id,
                'label' => $a->name,
            ])->all(),
        ];
    }
}
