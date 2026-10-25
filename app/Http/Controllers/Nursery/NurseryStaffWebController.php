<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nursery;

use App\Http\Controllers\Concerns\PersistsMorphAttachments;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Nursery\NurseryShift;
use App\Services\Nursery\NurseryDashboardService;
use App\Services\Nursery\NurseryStaffAccountService;
use App\Services\Nursery\NurseryStaffService;
use App\Support\NurseryAccess;
use App\Support\NurseryPermissionCatalog;
use App\Support\NurseryStaffPermissionGate;
use App\Support\SaudiRegions;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

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
            $permissions = $this->permissionsForLoginInvite($request, $permissions, $gate);
            $result = $staff->create($tenantUserId, $data, $permissions);
            $this->persistUploads($request, $result['employee'], $tenantUserId);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            return back()->withInput()->with('error', 'تعذّر الحفظ بسبب تكرار بيانات (بريد أو كود). استخدم بريداً مختلفاً.');
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'تعذّر حفظ الموظف أو إنشاء حساب الدخول. راجع سجلات الخادم أو ترحيلات قاعدة البيانات.');
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'حدث خطأ غير متوقع أثناء حفظ الموظف.');
        }

        $redirect = redirect()
            ->route('nursery.staff.index')
            ->with('success', filled($result['temporary_password'] ?? null)
                ? 'تم إضافة الموظف وإنشاء حساب الدخول. انسخ البيانات من البطاقة الصفراء أدناه قبل إغلاق الصفحة.'
                : 'تم إضافة الموظف.');

        return $this->withStaffLoginCredentialsFlash($redirect, $result);
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
            $permissions = $this->permissionsForLoginInvite($request, $permissions, $gate);
            $result = $staff->update($employee, $tenantUserId, $data, $permissions);
            $this->persistUploads($request, $result['employee'], $tenantUserId);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (UniqueConstraintViolationException $e) {
            report($e);

            return back()->withInput()->with('error', 'تعذّر الحفظ بسبب تكرار بيانات (بريد أو كود). استخدم بريداً مختلفاً.');
        } catch (QueryException $e) {
            report($e);

            return back()->withInput()->with('error', 'تعذّر حفظ الموظف أو إنشاء حساب الدخول. راجع سجلات الخادم أو ترحيلات قاعدة البيانات.');
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'حدث خطأ غير متوقع أثناء حفظ الموظف.');
        }

        if ($result['created'] && filled($result['temporary_password'] ?? null)) {
            $redirect = redirect()
                ->route('nursery.staff.index')
                ->with('success', 'تم تحديث الموظف وإنشاء حساب الدخول. انسخ البيانات من البطاقة الصفراء أدناه قبل إغلاق الصفحة.');

            return $this->withStaffLoginCredentialsFlash($redirect, $result);
        }

        return redirect()
            ->route('nursery.staff.index')
            ->with('success', 'تم تحديث بيانات الموظف.');
    }

    public function resetLoginPassword(
        Employee $employee,
        NurseryStaffAccountService $accounts,
    ): RedirectResponse {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        abort_unless((int) $employee->user_id === $tenantUserId, 404);

        try {
            $result = $accounts->resetLoginPassword($employee);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()
                ->route('nursery.staff.edit', $employee)
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()
            ->route('nursery.staff.edit', $employee)
            ->with('success', 'تم إنشاء كلمة مرور مؤقتة جديدة. انسخها من البطاقة الصفراء أدناه — لن تظهر مرة أخرى إلا بإعادة التعيين.');

        return $this->withStaffLoginCredentialsFlash($redirect, [
            'employee' => $employee->fresh() ?? $employee,
            'user' => $result['user'],
            'temporary_password' => $result['temporary_password'],
        ]);
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
            'nursery_job_role' => ['required', 'string', Rule::in($jobKeys)],
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

    /**
     * زر «حفظ وإنشاء حساب دخول» يفعّل صلاحية الدخول ويتطلب بريداً.
     *
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function permissionsForLoginInvite(Request $request, array $permissions, NurseryStaffPermissionGate $gate): array
    {
        if ((string) $request->input('submit_action') !== 'save_and_invite') {
            return $permissions;
        }

        if (! $gate->canGrant(null, 'login.app')) {
            throw new InvalidArgumentException('لا تملك صلاحية منح دخول النظام لهذا الموظف.');
        }

        if (! in_array('login.app', $permissions, true)) {
            $permissions[] = 'login.app';
        }

        return array_values(array_unique($permissions));
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

    /**
     * @param  array{employee?: Employee|null, user?: \App\Models\User|null, temporary_password?: ?string}  $result
     */
    private function withStaffLoginCredentialsFlash(RedirectResponse $redirect, array $result): RedirectResponse
    {
        $password = $result['temporary_password'] ?? null;
        if (! filled($password)) {
            return $redirect;
        }

        $email = (string) (($result['user']->email ?? null) ?: ($result['employee']->email ?? ''));

        return $redirect->with('staff_login_credentials', [
            'email' => $email,
            'password' => (string) $password,
            'employee_name' => (string) ($result['employee']->name ?? ''),
            'login_url' => route('nursery.login'),
        ]);
    }
}
