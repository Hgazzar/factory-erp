<?php

namespace App\Http\Controllers;

use App\Models\CrmLoyaltyAccount;
use App\Models\CrmLoyaltyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmLoyaltyWebController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) $request->user()->id;
        $status = trim((string) $request->string('status', ''));

        $base = CrmLoyaltyProgram::query()->forTenant($tenantId);

        $query = clone $base;

        if ($status !== '' && array_key_exists($status, CrmLoyaltyProgram::statusLabels())) {
            $query->where('status', $status);
        }

        $programs = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalFiltered = $programs->total();
        $totalAll = (clone $base)->count();

        return view('crm.loyalty.index', [
            'programs' => $programs,
            'statusOptions' => $this->statusOptions(),
            'totalAll' => $totalAll,
            'totalFiltered' => $totalFiltered,
        ]);
    }

    public function accounts(Request $request): View
    {
        $tenantId = (int) $request->user()->id;
        $programId = $request->input('loyalty_program_id');

        $programs = CrmLoyaltyProgram::query()
            ->forTenant($tenantId)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $programOptions = $programs
            ->map(fn (CrmLoyaltyProgram $p) => ['value' => (string) $p->id, 'label' => $p->code.' — '.$p->name])
            ->values()
            ->all();

        $query = CrmLoyaltyAccount::query()
            ->forTenant($tenantId)
            ->with([
                'customer:id,user_id,code,name,name_ar,first_name,last_name',
                'loyaltyProgram:id,code,name,redemption_rate,points_name',
            ]);

        if ($programId !== null && $programId !== '') {
            $query->where('loyalty_program_id', (int) $programId);
        }

        $accounts = $query
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view('crm.loyalty.accounts', [
            'accounts' => $accounts,
            'programOptions' => $programOptions,
            'totalFiltered' => $accounts->total(),
        ]);
    }

    public function create(Request $request): View
    {
        $tenantId = (int) $request->user()->id;

        return view('crm.loyalty.create', [
            'nextCode' => $this->nextCodeForTenant($tenantId),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;
        $statuses = array_keys(CrmLoyaltyProgram::statusLabels());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'name_ar' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'points_name' => ['required', 'string', 'max:120'],
            'earning_rate' => ['required', 'numeric', 'min:0'],
            'redemption_rate' => ['required', 'numeric', 'min:0'],
            'min_transaction_amount' => ['required', 'numeric', 'min:0'],
            'min_redemption_points' => ['required', 'numeric', 'min:0'],
            'max_redemption_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'earn_on_discounts' => ['nullable', 'boolean'],
            'earn_on_tax' => ['nullable', 'boolean'],
            'has_expiration' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date', Rule::requiredIf(fn () => $request->boolean('has_expiration'))],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', Rule::requiredIf(fn () => $request->boolean('has_expiration'))],
            'tiers_count' => ['required', 'integer', 'min:1', 'max:20'],
            'status' => ['required', Rule::in($statuses)],
        ]);

        CrmLoyaltyProgram::create([
            'user_id' => $tenantId,
            'code' => $this->nextCodeForTenant($tenantId),
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'description' => $data['description'] ?? null,
            'points_name' => $data['points_name'],
            'earning_rate' => $data['earning_rate'],
            'redemption_rate' => $data['redemption_rate'],
            'min_transaction_amount' => $data['min_transaction_amount'],
            'min_redemption_points' => $data['min_redemption_points'],
            'max_redemption_percentage' => $data['max_redemption_percentage'] ?? null,
            'earn_on_discounts' => $request->boolean('earn_on_discounts'),
            'earn_on_tax' => $request->boolean('earn_on_tax'),
            'has_expiration' => $request->boolean('has_expiration'),
            'start_date' => $request->boolean('has_expiration') ? ($data['start_date'] ?? null) : null,
            'end_date' => $request->boolean('has_expiration') ? ($data['end_date'] ?? null) : null,
            'tiers_count' => $data['tiers_count'],
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('crm.loyalty.index')
            ->with('success', 'تم إنشاء برنامج الولاء بنجاح.');
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function statusOptions(): array
    {
        return collect(CrmLoyaltyProgram::statusLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    private function nextCodeForTenant(int $tenantId): string
    {
        $codes = CrmLoyaltyProgram::query()
            ->forTenant($tenantId)
            ->where('code', 'like', 'LOY-%')
            ->pluck('code');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^LOY-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'LOY-'.str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
