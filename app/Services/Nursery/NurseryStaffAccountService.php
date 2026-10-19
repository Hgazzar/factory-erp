<?php

declare(strict_types=1);

namespace App\Services\Nursery;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class NurseryStaffAccountService
{
    /**
     * Ensure the employee has a login User linked via linked_user_id.
     *
     * @return array{user: User, created: bool, temporary_password: ?string}
     */
    public function ensureLoginUser(Employee $employee): array
    {
        if (! Schema::hasColumn('employees', 'linked_user_id')) {
            throw new RuntimeException(
                'عمود ربط حساب الدخول غير موجود على قاعدة البيانات. شغّل ترحيلات قاعدة البيانات ثم أعد المحاولة.'
            );
        }

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

            if (in_array((string) $existing->role, ['admin', 'super_admin'], true)) {
                throw new InvalidArgumentException('هذا البريد مرتبط بحساب موجود ولا يمكن ربطه بهذا الموظف.');
            }

            if ((string) $existing->role !== 'supervisor') {
                $existing->forceFill(['role' => 'supervisor'])->save();
            }

            $employee->forceFill(['linked_user_id' => $existing->id])->save();

            return [
                'user' => $existing->fresh() ?? $existing,
                'created' => false,
                'temporary_password' => null,
            ];
        }

        // أحرف وأرقام فقط — رموز خاصة كانت تسبب مشاكل عرض/جلسة على بعض البيئات.
        $temporaryPassword = Str::password(12, letters: true, numbers: true, symbols: false);

        try {
            $user = User::query()->create([
                'name' => (string) $employee->name,
                'email' => $email,
                'role' => 'supervisor',
                'is_technician' => false,
                // يُشفَّر عبر cast `hashed` على نموذج User
                'password' => $temporaryPassword,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new InvalidArgumentException('هذا البريد الإلكتروني مستخدم مسبقاً في النظام.', 0, $e);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new InvalidArgumentException('هذا البريد الإلكتروني مستخدم مسبقاً في النظام.', 0, $e);
            }

            report($e);
            throw new RuntimeException('تعذّر إنشاء حساب الدخول لهذا الموظف. تحقق من سجلات الخادم.', 0, $e);
        }

        try {
            $employee->forceFill(['linked_user_id' => $user->id])->save();
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException(
                'تم إنشاء المستخدم لكن تعذّر ربطه بالموظف. تحقق من عمود linked_user_id وترحيلات قاعدة البيانات.',
                0,
                $e
            );
        }

        return [
            'user' => $user,
            'created' => true,
            'temporary_password' => $temporaryPassword,
        ];
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $message = strtolower($e->getMessage());

        return $sqlState === '23505'
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }
}
