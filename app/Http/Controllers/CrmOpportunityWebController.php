<?php

namespace App\Http\Controllers;

use App\Models\CrmOpportunity;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmOpportunityWebController extends Controller
{
    /** تطبيع حقول اختيارية قبل التحقق (تواريخ فارغة، احتمالية، مسؤول). */
    private function prepareOpportunityPayload(Request $request): void
    {
        $prob = $request->input('probability');
        $probResolved = ($prob !== null && $prob !== '') ? (int) $prob : null;

        $request->merge([
            'next_follow_up_date' => $request->filled('next_follow_up_date') ? $request->input('next_follow_up_date') : null,
            'assigned_user_id' => $request->filled('assigned_user_id') ? $request->integer('assigned_user_id') : null,
            'probability' => $probResolved,
        ]);
    }

    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->id;

        $query = CrmOpportunity::forTenant($tenantId)
            ->with(['customer', 'assignedUser']);

        $stage = $request->input('stage');
        $stageKeys = collect(config('crm_opportunities.stages'))->pluck('value')->all();
        if ($stage !== null && $stage !== '' && in_array($stage, $stageKeys, true)) {
            $query->where('crm_opportunities.stage', $stage);
        }

        $qSearch = trim((string) $request->input('q', ''));
        if ($qSearch !== '') {
            $like = '%'.$qSearch.'%';
            $query->where(function ($w) use ($like) {
                $w->where('crm_opportunities.title', 'like', $like)
                    ->orWhere('crm_opportunities.opportunity_number', 'like', $like)
                    ->orWhereHas('customer', function ($c) use ($like) {
                        $c->where('customers.name', 'like', $like)
                            ->orWhere('customers.name_ar', 'like', $like)
                            ->orWhere('customers.company_name', 'like', $like)
                            ->orWhere('customers.code', 'like', $like);
                    });
            });
        }

        $query->orderByDesc('crm_opportunities.updated_at');

        $opportunities = $query->paginate(20)->withQueryString();

        $stageFilterOptions = collect(config('crm_opportunities.stages'))
            ->map(fn ($row) => ['value' => (string) ($row['value'] ?? ''), 'label' => (string) ($row['label'] ?? '')])
            ->values()
            ->all();

        return view('crm.opportunities.index', compact('opportunities', 'stageFilterOptions'));
    }

    public function show(Request $request, int $opportunity): View
    {
        $tenantId = (int) $request->user()->id;

        $model = CrmOpportunity::forTenant($tenantId)
            ->with(['customer', 'assignedUser'])
            ->findOrFail($opportunity);

        return view('crm.opportunities.show', ['opportunity' => $model]);
    }

    public function pipeline(Request $request): View
    {
        $tenantId = (int) $request->user()->id;

        $rows = CrmOpportunity::forTenant($tenantId)
            ->with(['customer', 'assignedUser'])
            ->orderByDesc('updated_at')
            ->get();

        $stageKeys = collect(config('crm_opportunities.stages'))->pluck('value')->all();
        $grouped = [];
        foreach ($stageKeys as $key) {
            $grouped[$key] = $rows->where('stage', $key)->values();
        }

        return view('crm.opportunities.pipeline', compact('grouped'));
    }

    public function create(Request $request): View
    {
        return $this->formView($request, null);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;
        $this->prepareOpportunityPayload($request);
        $stageKeys = collect(config('crm_opportunities.stages'))->pluck('value')->all();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('user_id', $tenantId))],
            'stage' => ['required', 'string', Rule::in($stageKeys)],
            'description' => ['nullable', 'string', 'max:20000'],
            'estimated_value' => ['required', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_closing_date' => ['required', 'date'],
            'next_follow_up_date' => ['nullable', 'date'],
            'competitor_notes' => ['nullable', 'string', 'max:20000'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $data['user_id'] = $tenantId;
        $data['estimated_value'] = $data['estimated_value'] ?? 0;
        $data['probability'] = $data['probability'] ?? 0;

        CrmOpportunity::create($data);

        return redirect()
            ->route('crm.opportunities.index')
            ->with('success', 'تم حفظ الفرصة بنجاح.');
    }

    public function edit(Request $request, int $opportunity): View
    {
        $tenantId = (int) $request->user()->id;
        $model = CrmOpportunity::forTenant($tenantId)->findOrFail($opportunity);

        return $this->formView($request, $model);
    }

    public function update(Request $request, int $opportunity): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;
        $model = CrmOpportunity::forTenant($tenantId)->findOrFail($opportunity);

        $this->prepareOpportunityPayload($request);

        $stageKeys = collect(config('crm_opportunities.stages'))->pluck('value')->all();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('user_id', $tenantId))],
            'stage' => ['required', 'string', Rule::in($stageKeys)],
            'description' => ['nullable', 'string', 'max:20000'],
            'estimated_value' => ['required', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_closing_date' => ['required', 'date'],
            'next_follow_up_date' => ['nullable', 'date'],
            'competitor_notes' => ['nullable', 'string', 'max:20000'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $data['estimated_value'] = $data['estimated_value'] ?? 0;
        $data['probability'] = $data['probability'] ?? 0;

        $model->update($data);

        return redirect()
            ->route('crm.opportunities.index')
            ->with('success', 'تم تحديث الفرصة بنجاح.');
    }

    /**
     * @param  \App\Models\CrmOpportunity|null  $opportunity
     */
    private function formView(Request $request, ?CrmOpportunity $opportunity): View
    {
        $tenantId = (int) $request->user()->id;

        $assigneeIds = Customer::forTenant($tenantId)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');
        $assigneeIds = $assigneeIds->push($tenantId)->unique()->filter();

        $assignees = User::whereIn('id', $assigneeIds)->orderBy('name')->get();

        $crmAssigneeFilterOptions = $assignees->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values()->all();

        $customerOptions = Customer::forTenant($tenantId)
            ->orderBy('name')
            ->get()
            ->map(fn (Customer $c) => [
                'value' => (string) $c->id,
                'label' => $c->display_name.($c->code ? ' — '.$c->code : ''),
            ])
            ->values()
            ->all();

        $stageOptions = config('crm_opportunities.stages');

        return view('crm.opportunities.form', [
            'opportunity' => $opportunity,
            'crmAssigneeFilterOptions' => $crmAssigneeFilterOptions,
            'customerOptions' => $customerOptions,
            'stageOptions' => $stageOptions,
        ]);
    }
}
