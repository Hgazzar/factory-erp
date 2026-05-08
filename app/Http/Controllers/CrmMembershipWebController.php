<?php

namespace App\Http\Controllers;

use App\Models\CrmCustomerMembership;
use App\Models\CrmLoyaltyProgram;
use App\Models\CrmMembership;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmMembershipWebController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->id;
        $q = trim((string) $request->string('q', ''));
        $status = trim((string) $request->string('status', ''));

        $base = CrmMembership::query()->forTenant($tenantId);
        $query = clone $base;

        if ($q !== '') {
            $query->where(function ($inner) use ($q): void {
                $inner->where('code', 'like', '%'.$q.'%')
                    ->orWhere('name', 'like', '%'.$q.'%');
            });
        }

        if ($status !== '' && array_key_exists($status, CrmMembership::statusLabels())) {
            $query->where('status', $status);
        }

        $memberships = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $statusOptions = collect(CrmMembership::statusLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return view('crm.memberships.index', [
            'memberships' => $memberships,
            'statusOptions' => $statusOptions,
            'totalAll' => (clone $base)->count(),
            'totalFiltered' => $memberships->total(),
        ]);
    }

    public function create(Request $request): View
    {
        $tenantId = (int) $request->user()->id;

        $customerOptions = Customer::queryForCrmUser($request->user())
            ->orderBy('customers.name')
            ->limit(500)
            ->get(['customers.id', 'customers.code', 'customers.name', 'customers.name_ar', 'customers.first_name', 'customers.last_name'])
            ->map(fn (Customer $customer) => [
                'value' => (string) $customer->id,
                'label' => trim($customer->code.' — '.$customer->display_name),
            ])
            ->values()
            ->all();

        $planOptions = CrmLoyaltyProgram::query()
            ->forTenant($tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (CrmLoyaltyProgram $plan) => [
                'value' => (string) $plan->id,
                'label' => trim($plan->code.' — '.$plan->name),
            ])
            ->values()
            ->all();

        return view('crm.memberships.create', [
            'customerOptions' => $customerOptions,
            'planOptions' => $planOptions,
            'nextMembershipCode' => CrmMembership::generateNextCodeForTenant($tenantId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;

        $customerIds = Customer::queryForCrmUser($request->user())->pluck('customers.id')->all();
        $planIds = CrmLoyaltyProgram::query()->forTenant($tenantId)->pluck('id')->all();

        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::in($customerIds)],
            'loyalty_program_id' => ['required', 'integer', Rule::in($planIds)],
            'start_date' => ['nullable', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $exists = CrmCustomerMembership::query()
            ->forTenant($tenantId)
            ->where('customer_id', (int) $data['customer_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['customer_id' => 'هذا العميل لديه اشتراك حالي بالفعل.'])
                ->withInput();
        }

        CrmCustomerMembership::query()->create([
            'user_id' => $tenantId,
            'customer_id' => (int) $data['customer_id'],
            'loyalty_program_id' => (int) $data['loyalty_program_id'],
            'start_date' => $data['start_date'] ?? null,
            'auto_renew' => $request->boolean('auto_renew', true),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('crm.memberships.index')
            ->with('success', 'تم إنشاء الاشتراك الجديد بنجاح.');
    }
}

