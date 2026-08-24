<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Nursery\NurseryShift;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryStaffService;
use App\Support\NurseryAccess;
use App\Support\NurseryPermissionCatalog;
use App\Support\NurseryStaffPermissionGate;
use App\Support\SaudiRegions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

final class NurseryStaffWebController extends Controller
{
    use PersistsMorphAttachments;
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $q = trim((string) $request->query('q', ''));
        $jobRole = trim((string) $request->query('job_role', ''));
        $status = trim((string) $request->query('status', ''));

        $base = Employee::query()->where('user_id', $tenantUserId);

        $listStats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'archived' => (clone $base)->where('status', 'inactive')->count(),
        ];

        $items = Employee::query()
            ->with(['attachments'])
            ->where('user_id', $tenantUserId)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('mobile', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            }))
            ->when($jobRole !== '', fn ($query) => $query->where('nursery_job_role', $jobRole))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $canManage = app(NurseryAccess::class)->allows(NurseryAccess::CAP_MANAGE_STAFF);

        return view('nursery.staff.index', [
            'items' => $items,
            'listStats' => $listStats,
            'spark' => app(NurseryDashboardService::class)->listSparkMeta($listStats),
            'q' => $q,
            'jobRole' => $jobRole,
            'status' => $status,
            'jobRoleOptions' => $this->jobRoleOptions(),
            'canManage' => $canManage,
        ]);
    }

    public function create(NurseryStaffPermissionGate $gate): View
    {
        return view('nursery.staff.create', $this->formViewData($gate));
    }

    public function store(Request $request, NurseryStaffService $staff, NurseryStaffPermissionGate $gate): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $this->validateStaffPayload($request);
        $permissions = $gate->filterGrantable(null, $request->input('permissions', []));

        try {
            $result = $staff->create($tenantUserId, $data, $permissions);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->persistUploads($request, $result['employee'], $tenantUserId);

        $success = 'تم إضافة الموظف وإنشاء حساب الدخول.';
        if (filled($result['temporary_password'] ?? null)) {
            $success .= ' البريد: '.($result['user']->email ?? $result['employee']->email)
                .' — كلمة المرور المؤقتة: '.$result['temporary_password'];
        }

        return redirect()
            ->route('nursery.staff.index')
            ->with('success', $success);
    }

    public function edit(Employee $employee, NurseryStaffPermissionGate $gate): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $employee->user_id === $tenantUserId, 404);

        $employee->load(['attachments']);

        return view('nursery.staff.edit', array_merge(
            $this->formViewData($gate),
            ['employee' => $employee]
        ));
    }

    public function update(Request $request, Employee $employee, NurseryStaffService $staff, NurseryStaffPermissionGate $gate): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $employee->user_id === $tenantUserId, 404);

        $data = $this->validateStaffPayload($request);
        $permissions = $this->permissionsForUpdate($gate, $employee, $request->input('permissions', []));

        try {
            $result = $staff->update($employee, $tenantUserId, $data, $permissions);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $this->persistUploads($request, $result['employee'], $tenantUserId);

        if ($result['created'] && filled($result['temporary_password'] ?? null)) {
            return redirect()
                ->route('nursery.staff.index')
                ->with(
                    'success',
                    'تم تحديث الموظف وإنشاء حساب الدخول. البريد: '
                    .($result['user']->email ?? $result['employee']->email)
                    .' — كلمة المرور المؤقتة: '.$result['temporary_password']
                );
        }

        return redirect()
            ->route('nursery.staff.index')
            ->with('success', 'تم تحديث بيانات الموظف.');
    }

    public function citySelectPartial(Request $request): View
    {
        return view('nursery.partials.staff-city-select', [
            'regionKey' => (string) $request->query('region', ''),
            'cityValue' => (string) $request->query('city', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formViewData(NurseryStaffPermissionGate $gate): array
    {
        $selected = NurseryPermissionCatalog::normalize(old('permissions', []));
        $tenantUserId = $this->resolveOperationsTenantUserId();

        return [
            'jobRoleOptions' => $this->jobRoleOptions(),
            'genderOptions' => [
                ['value' => 'male', 'label' => 'ذكر'],
                ['value' => 'female', 'label' => 'أنثى'],
            ],
            'systemRoleOptions' => [
                ['value' => NurseryAccess::ROLE_RECEPTION, 'label' => 'استقبال'],
                ['value' => NurseryAccess::ROLE_TEACHER, 'label' => 'معلمة'],
            ],
            'permissionGroups' => NurseryPermissionCatalog::groups(),
            'selectedPermissions' => $selected,
            'grantableKeys' => $gate->grantableKeys(),
            'canGrantAll' => count($gate->grantableKeys()) === count(NurseryPermissionCatalog::allKeys()),
            'rolePermissionTemplates' => [
                NurseryAccess::ROLE_TEACHER => NurseryPermissionCatalog::templateForRole(NurseryAccess::ROLE_TEACHER),
                NurseryAccess::ROLE_RECEPTION => NurseryPermissionCatalog::templateForRole(NurseryAccess::ROLE_RECEPTION),
            ],
            'shiftOptions' => NurseryShift::query()
                ->where('user_id', $tenantUserId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (NurseryShift $s) => ['value' => (string) $s->id, 'label' => $s->name.' ('.$s->formattedRange().')'])
                ->values()
                ->all(),
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function jobRoleOptions(): array
    {
        return collect(config('nursery_job_roles.roles', []))
            ->map(fn (string $label, string $key): array => ['value' => $key, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStaffPayload(Request $request): array
    {
        $regionKeys = array_keys(SaudiRegions::regions());
        $jobKeys = array_keys(config('nursery_job_roles.roles', []));

        return $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'id_number' => ['nullable', 'string', 'max:64'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'nursery_job_role' => ['nullable', 'string', Rule::in($jobKeys)],
            'nursery_education' => ['nullable', 'string', 'max:120'],
            'nursery_specialization' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['required', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'region' => ['nullable', 'string', Rule::in($regionKeys)],
            'city' => ['nullable', 'string', 'max:120'],
            'nursery_role' => ['nullable', 'string', 'in:,reception,teacher'],
            'nursery_shift_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(NurseryPermissionCatalog::allKeys())],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Disabled checkboxes are omitted from POST. Keep existing keys the editor cannot grant.
     *
     * @param  mixed  $requested
     * @return list<string>
     */
    private function permissionsForUpdate(NurseryStaffPermissionGate $gate, Employee $employee, mixed $requested): array
    {
        $posted = $gate->filterGrantable(null, $requested);
        $existing = NurseryPermissionCatalog::normalize($employee->nursery_permissions);
        $grantable = $gate->grantableKeys();
        $preserved = array_values(array_filter(
            $existing,
            fn (string $key): bool => ! in_array($key, $grantable, true)
        ));

        return array_values(array_unique(array_merge($preserved, $posted)));
    }

    private function persistUploads(Request $request, Employee $employee, int $tenantUserId): void
    {
        $uploads = $request->file('attachments', []) ?? [];
        if ($uploads !== []) {
            $this->persistMorphAttachments($employee, $uploads, $tenantUserId, 'nursery/staff');
        }
        $this->persistAvatarUpload(
            $employee,
            $request->file('avatar'),
            $tenantUserId,
            'nursery/staff',
            $request->boolean('remove_avatar')
        );
    }
}
