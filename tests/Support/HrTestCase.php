<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Employee;
use App\Models\Shift;

abstract class HrTestCase extends AccountingTestCase
{
    protected function createShift(array $overrides = []): Shift
    {
        return Shift::withoutGlobalScopes()->create(array_merge([
            'user_id' => $this->tenant->id,
            'code' => 'A',
            'name_ar' => 'وردية صباحية',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'grace_minutes' => 15,
            'is_night' => false,
            'is_active' => true,
        ], $overrides));
    }

    protected function createEmployee(?Shift $shift = null, array $overrides = []): Employee
    {
        $code = $overrides['code'] ?? 'EMP-'.fake()->unique()->numerify('###');

        return Employee::withoutGlobalScopes()->create(array_merge([
            'user_id' => $this->tenant->id,
            'shift_id' => $shift?->id,
            'code' => $code,
            'name' => 'موظف اختبار',
            'first_name' => 'موظف',
            'middle_name' => 'اختبار',
            'last_name' => 'نظام',
            'attendance_device_id' => $code,
            'status' => 'active',
            'base_salary' => 6000,
            'salary_type' => Employee::SALARY_MONTHLY,
            'attendance_policy' => Employee::ATTENDANCE_POLICY_HOUR_FOR_HOUR,
        ], $overrides));
    }
}
