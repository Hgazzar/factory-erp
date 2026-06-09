<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetAgent;
use App\Models\Fleet\FleetCustodyIssue;
use App\Models\Fleet\FleetProduct;
use App\Services\Fleet\FleetCustodyBalanceService;
use App\Services\Fleet\FleetCustodyIssueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class FleetCustodyWebController extends Controller
{
    use ResolvesOperationsTenant;

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            FleetCustodyIssue::STATUS_DRAFT => 'مسودة',
            FleetCustodyIssue::STATUS_ISSUED => 'مصروفة',
            FleetCustodyIssue::STATUS_VOID => 'ملغاة',
        ];
    }

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $agentId = (int) $request->query('agent_id', 0);
        $status = trim((string) $request->query('status', ''));

        $issues = FleetCustodyIssue::query()
            ->with(['agent:id,name'])
            ->withCount('lines')
            ->where('user_id', $tenantUserId)
            ->when($agentId > 0, fn ($q) => $q->where('agent_id', $agentId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('issued_on')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $agents = FleetAgent::query()
            ->where('user_id', $tenantUserId)
            ->where('status', FleetAgent::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('fleet.custody.index', [
            'issues' => $issues,
            'agents' => $agents,
            'agentId' => $agentId,
            'status' => $status,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function balances(FleetCustodyBalanceService $balances): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return view('fleet.custody.balances', [
            'rows' => $balances->summaryByAgent($tenantUserId),
        ]);
    }

    public function agentBalance(FleetAgent $agent, FleetCustodyBalanceService $balances): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        abort_if((int) $agent->user_id !== $tenantUserId, 403);

        $lines = $balances->linesForAgent($tenantUserId, (int) $agent->id);

        return view('fleet.custody.agent-balance', [
            'agent' => $agent,
            'lines' => $lines,
            'totalQty' => round($lines->sum(fn ($l) => (float) $l->quantity), 4),
            'totalValue' => round($lines->sum(fn ($l) => (float) $l->line_value), 4),
        ]);
    }

    public function create(): View
    {
        return view('fleet.custody.create', $this->formData($this->resolveOperationsTenantUserId()));
    }

    public function store(Request $request, FleetCustodyIssueService $custody): RedirectResponse
    {
        $data = $request->validate([
            'agent_id' => ['required', 'integer', 'min:1'],
            'issued_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $issue = $custody->createDraft(
                $this->resolveOperationsTenantUserId(),
                (int) $data['agent_id'],
                (string) $data['issued_on'],
                $data['lines'],
                $data['notes'] ?? null,
                auth()->id() ? (int) auth()->id() : null,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['custody' => $e->getMessage()]);
        }

        return redirect()->route('fleet.custody.show', $issue)->with('success', 'تم حفظ مسودة العهدة.');
    }

    public function show(FleetCustodyIssue $custody): View
    {
        $custody->load(['agent:id,name,phone', 'lines.product:id,name,sku,sale_price']);

        return view('fleet.custody.show', [
            'issue' => $custody,
            'statusLabels' => $this->statusLabels(),
            'totalQty' => round($custody->lines->sum('quantity'), 4),
            'totalValue' => round($custody->lines->sum(fn ($l) => (float) $l->quantity * (float) $l->unit_price), 4),
        ]);
    }

    public function confirm(FleetCustodyIssue $custody, FleetCustodyIssueService $service): RedirectResponse
    {
        try {
            $service->confirm($custody, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['custody' => $e->getMessage()]);
        }

        return back()->with('success', 'تم تأكيد صرف العهدة للمندوب.');
    }

    public function void(FleetCustodyIssue $custody, FleetCustodyIssueService $service): RedirectResponse
    {
        try {
            $service->void($custody, $this->resolveOperationsTenantUserId());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['custody' => $e->getMessage()]);
        }

        return redirect()->route('fleet.custody.index')->with('success', 'تم إلغاء سند العهدة.');
    }

    /** @return array<string, mixed> */
    private function formData(int $tenantUserId): array
    {
        $products = FleetProduct::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sale_price']);

        return [
            'agents' => FleetAgent::query()
                ->where('user_id', $tenantUserId)
                ->where('status', FleetAgent::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => $products,
            'productOptions' => $products->map(fn (FleetProduct $p): array => [
                'value' => (string) $p->id,
                'label' => trim($p->name.($p->sku ? " ({$p->sku})" : '')),
            ])->all(),
        ];
    }
}
