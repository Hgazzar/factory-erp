<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Models\CrmActivity;
use App\Models\CrmAppointment;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmCustomerWebController extends Controller
{
    use PersistsMorphAttachments;

    public const CRM_STATUSES = ['potential', 'interested', 'active', 'not_interested'];

    /** أولوية العميل المحتمل (قيم مخزّنة إنجليزية، عرض عربي في الواجهة). */
    public const LEAD_PRIORITIES = ['high', 'medium', 'low'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $tenantId = (int) $user->id;

        $isLeadsView = $request->query('crm_status') === 'potential';

        $crmStatus = $request->input('crm_status');
        $assignedUserId = $request->input('assigned_user_id');
        $source = trim((string) $request->input('source', ''));
        $leadPriority = $request->input('lead_priority');
        $qSearch = trim((string) $request->input('q', ''));
        $contactsStats = null;
        $sortForView = null;

        if ($isLeadsView) {
            $query = Customer::forTenant($tenantId)
                ->with('assignedUser')
                ->where('customers.crm_status', 'potential');

            if ($assignedUserId !== null && $assignedUserId !== '') {
                $query->where('customers.assigned_user_id', (int) $assignedUserId);
            }

            if ($source !== '') {
                $query->where('customers.source', $source);
            }

            if (in_array($leadPriority, self::LEAD_PRIORITIES, true)) {
                $query->where('customers.lead_priority', $leadPriority);
            }

            if ($qSearch !== '') {
                $like = '%'.$qSearch.'%';
                $query->where(function ($w) use ($like) {
                    $w->where('customers.name', 'like', $like)
                        ->orWhere('customers.first_name', 'like', $like)
                        ->orWhere('customers.last_name', 'like', $like)
                        ->orWhere('customers.company_name', 'like', $like)
                        ->orWhere('customers.name_ar', 'like', $like)
                        ->orWhere('customers.email', 'like', $like)
                        ->orWhere('customers.phone', 'like', $like)
                        ->orWhere('customers.mobile', 'like', $like)
                        ->orWhere('customers.lead_number', 'like', $like);
                });
            }

            $query->orderByDesc('customers.created_at');
        } else {
            $baseStatsQuery = Customer::queryForCrmUser($user);
            $contactsStats = [
                'total' => (clone $baseStatsQuery)->count(),
                'leads' => (clone $baseStatsQuery)->where('customers.crm_status', 'potential')->count(),
                'customers_active' => (clone $baseStatsQuery)->where('customers.crm_status', 'active')->count(),
            ];

            $sortColumn = $request->input('sort', 'created_at');
            if (! in_array($sortColumn, ['name', 'created_at'], true)) {
                $sortColumn = 'created_at';
            }
            $sortDirection = strtolower((string) $request->input('direction', $sortColumn === 'created_at' ? 'desc' : 'asc'));
            if (! in_array($sortDirection, ['asc', 'desc'], true)) {
                $sortDirection = 'desc';
            }

            $query = Customer::queryForCrmUser($user)->with('assignedUser');

            if (in_array($crmStatus, self::CRM_STATUSES, true)) {
                $query->where('customers.crm_status', $crmStatus);
            }

            if ($qSearch !== '') {
                $like = '%'.$qSearch.'%';
                $query->where(function ($w) use ($like) {
                    $w->where('customers.name', 'like', $like)
                        ->orWhere('customers.first_name', 'like', $like)
                        ->orWhere('customers.last_name', 'like', $like)
                        ->orWhere('customers.company_name', 'like', $like)
                        ->orWhere('customers.name_ar', 'like', $like)
                        ->orWhere('customers.email', 'like', $like)
                        ->orWhere('customers.phone', 'like', $like)
                        ->orWhere('customers.mobile', 'like', $like)
                        ->orWhere('customers.code', 'like', $like)
                        ->orWhere('customers.lead_number', 'like', $like);
                });
            }

            if ($sortColumn === 'created_at') {
                $query->orderBy('customers.created_at', $sortDirection);
            } else {
                $query->orderBy('customers.name', $sortDirection);
            }

            $sortForView = compact('sortColumn', 'sortDirection');
        }

        $customers = $query->paginate(20)->withQueryString();

        $assigneeIds = Customer::forTenant($tenantId)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');
        $assigneeIds = $assigneeIds->push($tenantId)->unique()->filter();

        $assignees = User::whereIn('id', $assigneeIds)->orderBy('name')->get();

        $sourceOptions = Customer::forTenant($tenantId)
            ->whereNotNull('source')
            ->where('source', '!=', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        return view('crm.customers.index', compact(
            'customers',
            'assignees',
            'sourceOptions',
            'isLeadsView',
            'contactsStats',
            'sortForView',
        ))->with('crmFollowUpTypeOptions', CrmActivity::followUpTypesForModal());
    }

    public function show(Request $request, Customer $customer): View
    {
        $this->authorizeCrmCustomer($request, $customer);

        $customer->load([
            'attachments',
            'crmActivities.user',
            'assignedUser',
        ]);

        return view('crm.customers.show', [
            'customer' => $customer,
        ])->with('crmFollowUpTypeOptions', CrmActivity::followUpTypesForModal());
    }

    public function createNewCustomer(Request $request): View
    {
        $user = $request->user();
        $tenantId = (int) $user->id;

        $nextCode = Customer::generateNextCodeForUser($tenantId);

        $assigneeIds = Customer::forTenant($tenantId)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');
        $assigneeIds = $assigneeIds->push($tenantId)->unique()->filter();

        $assignees = User::whereIn('id', $assigneeIds)->orderBy('name')->get();
        $crmAssigneeFilterOptions = $assignees->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values()->all();

        $crmStatusFilterOptions = [
            ['value' => 'potential', 'label' => 'محتمل'],
            ['value' => 'interested', 'label' => 'مهتم'],
            ['value' => 'active', 'label' => 'نشط'],
            ['value' => 'not_interested', 'label' => 'غير مهتم'],
        ];
        $crmLeadPriorityOptions = [
            ['value' => 'high', 'label' => 'عالية'],
            ['value' => 'medium', 'label' => 'متوسطة'],
            ['value' => 'low', 'label' => 'منخفضة'],
        ];

        return view('crm.customers.new', [
            'nextCode' => $nextCode,
            'crmAssigneeFilterOptions' => $crmAssigneeFilterOptions,
            'crmStatusFilterOptions' => $crmStatusFilterOptions,
            'crmLeadPriorityOptions' => $crmLeadPriorityOptions,
        ])->with('crmFollowUpTypeOptions', CrmActivity::followUpTypesForModal());
    }

    public function storeNewCustomer(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;

        $request->merge([
            'phone' => $request->filled('phone') ? $request->phone : null,
            'mobile' => $request->filled('mobile') ? $request->mobile : null,
            'vat_number' => $request->filled('vat_number') ? $request->vat_number : null,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'phone')->where('user_id', $tenantId)],
            'mobile' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'vat_number')->where('user_id', $tenantId)],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'crm_status' => ['required', Rule::in(self::CRM_STATUSES)],
            'source' => ['nullable', 'string', 'max:120'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'lead_priority' => ['nullable', Rule::in(self::LEAD_PRIORITIES)],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv'],
        ], [
            'phone.unique' => 'هذا العميل (رقم الهاتف) مسجل مسبقاً في النظام.',
            'vat_number.unique' => 'هذا العميل (رقم ضريبي VAT) مسجل مسبقاً في النظام.',
        ]);

        $data['user_id'] = $tenantId;
        $data['code'] = Customer::generateNextCodeForUser($tenantId);
        $data['credit_limit'] = isset($data['credit_limit']) && $data['credit_limit'] !== '' ? (float) $data['credit_limit'] : null;
        $data['payment_terms_days'] = isset($data['payment_terms_days']) && $data['payment_terms_days'] !== '' ? (int) $data['payment_terms_days'] : null;
        $data['tax_number'] = $data['vat_number'] ?? null;
        $data['assigned_user_id'] = ! empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $data['lead_priority'] = ! empty($data['lead_priority']) ? $data['lead_priority'] : null;
        foreach (['name_ar', 'email', 'address', 'country', 'city', 'region', 'postal_code', 'source'] as $key) {
            if (($data[$key] ?? '') === '') {
                $data[$key] = null;
            }
        }

        $uploads = $request->file('attachments', []) ?? [];
        if (! is_array($uploads)) {
            $uploads = [];
        }

        try {
            $customer = Customer::create($data);
            $this->persistMorphAttachments($customer, $uploads, $tenantId, 'customers');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'تعذر حفظ العميل حالياً. يرجى المحاولة لاحقاً.');
        }

        return redirect()
            ->route('crm.customers.show', $customer)
            ->with('success', 'تم إضافة العميل بنجاح.');
    }

    public function createLead(Request $request): View
    {
        $user = $request->user();
        $tenantId = (int) $user->id;

        $assigneeIds = Customer::forTenant($tenantId)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');
        $assigneeIds = $assigneeIds->push($tenantId)->unique()->filter();

        $assignees = User::whereIn('id', $assigneeIds)->orderBy('name')->get();

        $crmAssigneeFilterOptions = $assignees->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name])->values()->all();

        return view('crm.customers.create-lead', [
            'assignees' => $assignees,
            'crmAssigneeFilterOptions' => $crmAssigneeFilterOptions,
            'leadSourceOptions' => config('crm_lead_form.sources'),
            'leadSectorOptions' => config('crm_lead_form.sectors'),
            'leadCompanySizeOptions' => config('crm_lead_form.company_sizes'),
            'crmLeadRatingModalOptions' => collect(range(1, 5))->map(fn ($s) => ['value' => (string) $s, 'label' => (string) $s])->values()->all(),
            'crmLeadPriorityOptions' => [
                ['value' => 'high', 'label' => 'عالية'],
                ['value' => 'medium', 'label' => 'متوسطة'],
                ['value' => 'low', 'label' => 'منخفضة'],
            ],
        ])->with('crmFollowUpTypeOptions', CrmActivity::followUpTypesForModal());
    }

    public function storeLead(Request $request): RedirectResponse
    {
        $tenantId = (int) $request->user()->id;

        $sourceKeys = collect(config('crm_lead_form.sources'))->pluck('value')->all();
        $sectorKeys = collect(config('crm_lead_form.sectors'))->pluck('value')->all();
        $sizeKeys = collect(config('crm_lead_form.company_sizes'))->pluck('value')->all();

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'source' => ['required', Rule::in($sourceKeys)],
            'source_details' => ['nullable', 'string', 'max:500'],
            'lead_priority' => ['required', Rule::in(self::LEAD_PRIORITIES)],
            'lead_sector' => ['nullable', Rule::in($sectorKeys)],
            'lead_company_size' => ['nullable', Rule::in($sizeKeys)],
            'lead_budget' => ['nullable', 'numeric', 'min:0'],
            'lead_description' => ['nullable', 'string', 'max:10000'],
            'lead_requirements' => ['nullable', 'string', 'max:10000'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'lead_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $data['user_id'] = $tenantId;
        $data['code'] = Customer::generateNextCodeForUser($tenantId);
        $data['name'] = trim($data['first_name'].' '.$data['last_name']);
        $data['crm_status'] = 'potential';
        $data['status'] = 'active';
        $data['is_active'] = true;

        $data['website'] = $this->normalizeLeadWebsite($data['website'] ?? null);

        foreach (['company_name', 'job_title', 'email', 'phone', 'mobile', 'address', 'city', 'region', 'postal_code', 'country', 'source_details', 'lead_description', 'lead_requirements'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $data['assigned_user_id'] = ! empty($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : null;
        $data['lead_sector'] = ($data['lead_sector'] ?? '') !== '' ? $data['lead_sector'] : null;
        $data['lead_company_size'] = ($data['lead_company_size'] ?? '') !== '' ? $data['lead_company_size'] : null;
        $data['lead_budget'] = isset($data['lead_budget']) && $data['lead_budget'] !== '' ? $data['lead_budget'] : null;
        $data['lead_rating'] = isset($data['lead_rating']) && $data['lead_rating'] !== '' ? (int) $data['lead_rating'] : null;

        $customer = Customer::create($data);

        return redirect()
            ->route('crm.customers.show', $customer)
            ->with('success', 'تم إنشاء العميل المحتمل بنجاح.');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = (int) $user->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'crm_status' => ['required', Rule::in(self::CRM_STATUSES)],
            'source' => ['nullable', 'string', 'max:120'],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'lead_priority' => ['nullable', Rule::in(self::LEAD_PRIORITIES)],
            'lead_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $data['user_id'] = $tenantId;
        $data['code'] = Customer::generateNextCodeForUser($tenantId);
        $data['crm_status'] = $data['crm_status'] ?? 'potential';
        if (empty($data['assigned_user_id'])) {
            $data['assigned_user_id'] = null;
        }
        if (empty($data['lead_priority'])) {
            $data['lead_priority'] = null;
        }
        if (! isset($data['lead_rating']) || $data['lead_rating'] === null || $data['lead_rating'] === '') {
            $data['lead_rating'] = null;
        }
        $data['status'] = 'active';
        $data['is_active'] = true;

        Customer::create($data);

        $redirectQuery = $request->boolean('redirect_to_leads')
            ? ['crm_status' => 'potential']
            : [];

        return redirect()
            ->route('crm.customers.index', $redirectQuery)
            ->with('success', 'تم إضافة العميل بنجاح.');
    }

    public function storeQuickAppointment(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCrmCustomer($request, $customer);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'result' => ['nullable', 'string', 'max:255'],
        ]);

        CrmAppointment::withoutGlobalScopes()->create([
            'user_id' => (int) $request->user()->id,
            'customer_id' => $customer->id,
            'scheduled_at' => $data['scheduled_at'],
            'title' => $data['title'] ?? null,
        ]);

        $scheduled = Carbon::parse($data['scheduled_at'])->timezone(config('app.timezone'))->format('Y/m/d H:i');
        $noteLines = ['الموعد: '.$scheduled];
        if (! empty($data['title'])) {
            $noteLines[] = 'العنوان: '.$data['title'];
        }
        CrmActivity::create([
            'user_id' => (int) $request->user()->id,
            'customer_id' => $customer->id,
            'type' => CrmActivity::TYPE_APPOINTMENT,
            'note' => implode("\n", $noteLines),
            'result' => $data['result'] !== null && $data['result'] !== '' ? $data['result'] : 'مجدول',
        ]);

        $customer->update(['crm_last_activity_at' => now()]);

        return back()->with('success', 'تم حفظ الموعد.');
    }

    public function storeCallLog(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCrmCustomer($request, $customer);

        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(CrmActivity::followUpTypesForModal()))],
            'note' => ['nullable', 'string', 'max:5000'],
            'result' => ['nullable', 'string', 'max:255'],
        ]);

        CrmActivity::create([
            'user_id' => (int) $request->user()->id,
            'customer_id' => $customer->id,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'result' => $data['result'] ?? null,
        ]);

        $customer->update(['crm_last_activity_at' => now()]);

        return back()->with('success', 'تم حفظ سجل المتابعة وتحديث آخر نشاط.');
    }

    private function normalizeLeadWebsite(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }
        $w = trim($url);
        if (! preg_match('#^https?://#i', $w)) {
            $w = 'https://'.$w;
        }

        return mb_substr($w, 0, 500);
    }

    private function authorizeCrmCustomer(Request $request, Customer $customer): void
    {
        if ((int) $customer->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
