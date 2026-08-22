<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class NurseryStaffAccountService
{
    /**
     * Ensure the employee has a login User linked via linked_user_id.
     *
     * @return array{user: User, created: bool, temporary_password: ?string}
     */
    public function ensureLoginUser(Employee $employee): array
    {
        if ($employee->linked_user_id) {
            $linked = User::query()->find($employee->linked_user_id);
            if ($linked instanceof User) {
                return [
                    'user' => $linked,
                    'created' => false,
                    'temporary_password' => null,
                ];
            }

            $employee->linked_user_id = null;
        }

        $email = strtolower(trim((string) $employee->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('البريد الإلكتروني مطلوب لإنشاء حساب الدخول.');
        }

        $existing = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existing instanceof User) {
            $linkedEmployee = Employee::withoutGlobalScopes()
                ->where('linked_user_id', $existing->id)
                ->first();

            if ($linkedEmployee instanceof Employee && (int) $linkedEmployee->id !== (int) $employee->id) {
                throw new InvalidArgumentException('هذا البريد مرتبط بموظف آخر ولا يمكن استخدامه.');
            }

            if ($linkedEmployee instanceof Employee && (int) $linkedEmployee->id === (int) $employee->id) {
                $employee->forceFill(['linked_user_id' => $existing->id])->save();

                return [
                    'user' => $existing,
                    'created' => false,
                    'temporary_password' => null,
                ];
            }

            if (in_array($existing->role, ['admin', 'super_admin'], true)) {
                throw new InvalidArgumentException('هذا البريد مرتبط بحساب موجود ولا يمكن ربطه بهذا الموظف.');
            }

            if ($existing->role === 'worker') {
                $existing->forceFill(['role' => 'supervisor'])->save();
            } elseif ($existing->role !== 'supervisor') {
                $existing->forceFill(['role' => 'supervisor'])->save();
            }

            $employee->forceFill(['linked_user_id' => $existing->id])->save();

            return [
                'user' => $existing->fresh(),
                'created' => false,
                'temporary_password' => null,
            ];
        }

        $temporaryPassword = Str::password(12);

        $user = User::query()->create([
            'name' => (string) $employee->name,
            'email' => $email,
            'role' => 'supervisor',
            'password' => $temporaryPassword,
        ]);

        $employee->forceFill(['linked_user_id' => $user->id])->save();

        return [
            'user' => $user,
            'created' => true,
            'temporary_password' => $temporaryPassword,
        ];
    }
}
