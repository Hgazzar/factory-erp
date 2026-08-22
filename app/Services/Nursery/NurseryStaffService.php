<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Support\NurseryAccess;
use App\Support\NurseryPermissionCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class NurseryStaffService
{
    public function __construct(
        private readonly NurseryStaffAccountService $accounts,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $permissions
     * @return array{employee: Employee, user: \App\Models\User, created: bool, temporary_password: ?string}
     */
    public function create(int $tenantUserId, array $data, array $permissions): array
    {
        $payload = $this->buildPayload($data, $permissions, true);

        return DB::transaction(function () use ($tenantUserId, $payload): array {
            $payload['user_id'] = $tenantUserId;
            $payload['code'] = $this->nextCode($tenantUserId);
            $payload['status'] = $payload['status'] ?? 'active';

            $employee = Employee::query()->create($payload);
            $account = $this->accounts->ensureLoginUser($employee);

            return [
                'employee' => $employee->fresh(),
                'user' => $account['user'],
                'created' => $account['created'],
                'temporary_password' => $account['temporary_password'],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $permissions
     * @return array{employee: Employee, user: \App\Models\User, created: bool, temporary_password: ?string}
     */
    public function update(Employee $employee, int $tenantUserId, array $data, array $permissions): array
    {
        abort_unless((int) $employee->user_id === $tenantUserId, 404);

        return DB::transaction(function () use ($employee, $data, $permissions): array {
            $payload = $this->buildPayload($data, $permissions, false);
            $employee->fill($payload);
            $employee->save();

            $account = $this->accounts->ensureLoginUser($employee);

            return [
                'employee' => $employee->fresh(['attachments']),
                'user' => $account['user'],
                'created' => $account['created'],
                'temporary_password' => $account['temporary_password'],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $permissions
     * @return array<string, mixed>
     */
    private function buildPayload(array $data, array $permissions, bool $fillRoleTemplateWhenEmpty): array
    {
        $first = trim((string) ($data['first_name'] ?? ''));
        $last = trim((string) ($data['last_name'] ?? ''));
        $name = trim($first.' '.$last);

        if ($name === '') {
            throw new InvalidArgumentException('الاسم الأول واسم العائلة مطلوبان.');
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            throw new InvalidArgumentException('البريد الإلكتروني مطلوب.');
        }

        $mobile = trim((string) ($data['mobile'] ?? ''));
        if ($mobile === '') {
            throw new InvalidArgumentException('رقم الجوال مطلوب.');
        }

        $systemRole = (string) ($data['nursery_role'] ?? '');
        if (! in_array($systemRole, ['', NurseryAccess::ROLE_RECEPTION, NurseryAccess::ROLE_TEACHER], true)) {
            $systemRole = '';
        }

        if ($fillRoleTemplateWhenEmpty && $permissions === [] && $systemRole !== '') {
            $permissions = NurseryPermissionCatalog::templateForRole($systemRole);
        }

        return [
            'first_name' => $first,
            'last_name' => $last,
            'name' => $name,
            'id_number' => $data['id_number'] ?? null,
            'gender' => $data['gender'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'email' => $email,
            'mobile' => $mobile,
            'phone' => $mobile,
            'address' => $data['address'] ?? null,
            'region' => $data['region'] ?? null,
            'city' => $data['city'] ?? null,
            'nursery_job_role' => $data['nursery_job_role'] ?? null,
            'nursery_education' => $data['nursery_education'] ?? null,
            'nursery_specialization' => $data['nursery_specialization'] ?? null,
            'nursery_role' => $systemRole !== '' ? $systemRole : null,
            'nursery_permissions' => $permissions,
            'nursery_shift_id' => ! empty($data['nursery_shift_id']) ? (int) $data['nursery_shift_id'] : null,
            'status' => ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    private function nextCode(int $tenantUserId): string
    {
        $count = Employee::withoutGlobalScopes()
            ->where('user_id', $tenantUserId)
            ->count();

        return 'EMP-'.str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
