<?php

namespace App\Http\Controllers;

use App\Models\CrmActivity;
use App\Models\CrmAppointment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CrmAppointmentWebController extends Controller
{
    public function index(Request $request): View
    {
        $tenantId = (int) Auth::id();
        $q = trim((string) $request->string('q', ''));
        $type = trim((string) $request->string('type', ''));
        $status = trim((string) $request->string('status', ''));

        $base = CrmAppointment::forTenant($tenantId)->with(['customer', 'assignee']);

        if ($q !== '') {
            $base->where(function ($inner) use ($q): void {
                $inner->where('appointment_number', 'like', '%'.$q.'%')
                    ->orWhere('title', 'like', '%'.$q.'%')
                    ->orWhere('location', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', function ($qCustomer) use ($q): void {
                        $qCustomer
                            ->where('name', 'like', '%'.$q.'%')
                            ->orWhere('name_ar', 'like', '%'.$q.'%')
                            ->orWhere('first_name', 'like', '%'.$q.'%')
                            ->orWhere('last_name', 'like', '%'.$q.'%');
                    });
            });
        }

        if ($type !== '' && array_key_exists($type, CrmAppointment::typeLabels())) {
            $base->where('type', $type);
        }

        if ($status !== '' && array_key_exists($status, CrmAppointment::statusLabels())) {
            $base->where('status', $status);
        }

        $appointments = (clone $base)
            ->orderByDesc('start_at')
            ->paginate(20)
            ->withQueryString();

        $calendarEvents = (clone $base)
            ->orderBy('start_at')
            ->get()
            ->map(function (CrmAppointment $appointment): array {
                $titlePrefix = CrmAppointment::typeLabels()[$appointment->type] ?? 'موعد';

                return [
                    'title' => $titlePrefix.' - '.($appointment->customer?->display_name ?? 'عميل'),
                    'start' => optional($appointment->start_at)->toIso8601String(),
                    'end' => optional($appointment->end_at)->toIso8601String(),
                    'backgroundColor' => CrmAppointment::statusColors()[$appointment->status] ?? '#00E9F9',
                    'borderColor' => CrmAppointment::statusColors()[$appointment->status] ?? '#00E9F9',
                    'textColor' => '#0F172A',
                ];
            })
            ->values()
            ->all();

        $customerOptions = $this->customerOptions($request);
        $typeOptions = $this->appointmentTypeOptions();
        $statusOptions = $this->appointmentStatusOptions();
        $assigneeOptions = $this->assigneeOptions($request);

        return view('crm.appointments.index', compact(
            'appointments',
            'calendarEvents',
            'customerOptions',
            'typeOptions',
            'statusOptions',
            'assigneeOptions',
        ));
    }

    public function create(Request $request): View
    {
        return view('crm.appointments.create', [
            'customerOptions' => $this->customerOptions($request),
            'typeOptions' => $this->appointmentTypeOptions(),
            'statusOptions' => $this->appointmentStatusOptions(),
            'assigneeOptions' => $this->assigneeOptions($request),
            'nextAppointmentNumber' => CrmAppointment::generateNextNumberForTenant((int) Auth::id()),
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = (int) Auth::id();

        $customerIds = Customer::queryForCrmUser($request->user())
            ->pluck('customers.id')
            ->all();

        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::in($customerIds)],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(CrmAppointment::typeLabels()))],
            'status' => ['required', Rule::in(array_keys(CrmAppointment::statusLabels()))],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        CrmAppointment::query()->create([
            'user_id' => $tenantId,
            'appointment_number' => CrmAppointment::generateNextNumberForTenant($tenantId),
            'customer_id' => (int) $data['customer_id'],
            'title' => $data['title'],
            'type' => $data['type'],
            'status' => $data['status'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'] ?? null,
            'location' => $data['location'] ?? null,
            'assigned_to' => $data['assigned_to'] ? (int) $data['assigned_to'] : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('crm.appointments.index')
            ->with('success', 'تم إنشاء الموعد بنجاح.');
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function appointmentTypeOptions(): array
    {
        return collect(CrmAppointment::typeLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function appointmentStatusOptions(): array
    {
        return collect(CrmAppointment::statusLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function customerOptions(Request $request): array
    {
        return Customer::queryForCrmUser($request->user())
            ->select(['customers.id', 'customers.code', 'customers.name', 'customers.name_ar', 'customers.first_name', 'customers.last_name'])
            ->orderBy('customers.name')
            ->limit(500)
            ->get()
            ->map(fn (Customer $customer) => [
                'value' => (string) $customer->id,
                'label' => trim($customer->code.' — '.$customer->display_name),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function assigneeOptions(Request $request): array
    {
        return [[
            'value' => (string) Auth::id(),
            'label' => (string) ($request->user()->name ?? 'أنا'),
        ]];
    }

    public function activities(Request $request): View
    {
        $q = trim((string) $request->string('q', ''));
        $type = trim((string) $request->string('type', ''));

        $activitiesBaseQuery = CrmActivity::query()
            ->with([
                'customer:id,name,name_ar,first_name,last_name',
                'user:id,name',
            ]);

        if ($q !== '') {
            $activitiesBaseQuery->where(function ($query) use ($q): void {
                $query
                    ->where('note', 'like', "%{$q}%")
                    ->orWhere('result', 'like', "%{$q}%")
                    ->orWhereHas('customer', function ($qCustomer) use ($q): void {
                        $qCustomer
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('name_ar', 'like', "%{$q}%")
                            ->orWhere('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('user', fn ($qUser) => $qUser->where('name', 'like', "%{$q}%"));
            });
        }

        if ($type !== '' && array_key_exists($type, CrmActivity::typeLabels())) {
            $activitiesBaseQuery->where('type', $type);
        }

        $activities = (clone $activitiesBaseQuery)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $statsQuery = CrmActivity::query();

        if ($q !== '') {
            $statsQuery->where(function ($query) use ($q): void {
                $query
                    ->where('note', 'like', "%{$q}%")
                    ->orWhere('result', 'like', "%{$q}%")
                    ->orWhereHas('customer', function ($qCustomer) use ($q): void {
                        $qCustomer
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('name_ar', 'like', "%{$q}%")
                            ->orWhere('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('user', fn ($qUser) => $qUser->where('name', 'like', "%{$q}%"));
            });
        }

        if ($type !== '' && array_key_exists($type, CrmActivity::typeLabels())) {
            $statsQuery->where('type', $type);
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'today' => (clone $statsQuery)->whereDate('created_at', now()->toDateString())->count(),
            'completed' => (clone $statsQuery)->whereNotNull('result')->where('result', '!=', '')->count(),
            'pending' => (clone $statsQuery)->where(function ($query): void {
                $query->whereNull('result')->orWhere('result', '');
            })->count(),
        ];

        return view('crm.activities-placeholder', [
            'activities' => $activities,
            'stats' => $stats,
            'activityTypeOptions' => $this->activityTypeOptions(),
        ]);
    }

    public function createActivity(): View
    {
        return view('crm.activities.create', [
            'activityTypeOptions' => $this->activityTypeOptions(),
            'customerOptions' => $this->activityCustomerOptions(),
        ]);
    }

    public function storeActivity(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'type' => ['required', 'string', Rule::in(array_keys(CrmActivity::typeLabels()))],
            'note' => ['nullable', 'string', 'max:2000'],
            'result' => ['nullable', 'string', 'max:255'],
        ], [], [
            'customer_id' => 'العميل',
            'type' => 'نوع النشاط',
            'note' => 'الملاحظة',
            'result' => 'النتيجة',
        ]);

        CrmActivity::create([
            'customer_id' => (int) $data['customer_id'],
            'user_id' => (int) Auth::id(),
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'result' => $data['result'] ?? null,
        ]);

        return redirect()
            ->route('crm.activities.index')
            ->with('success', 'تمت إضافة النشاط بنجاح.');
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function activityTypeOptions(): array
    {
        return collect(CrmActivity::typeLabels())
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function activityCustomerOptions(): array
    {
        return Customer::queryForCrmUser(Auth::user())
            ->select(['customers.id', 'customers.name', 'customers.name_ar', 'customers.first_name', 'customers.last_name'])
            ->orderBy('customers.name')
            ->limit(500)
            ->get()
            ->map(fn (Customer $customer) => [
                'value' => (string) $customer->id,
                'label' => $customer->display_name,
            ])
            ->values()
            ->all();
    }
}
