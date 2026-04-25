<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeService
{
    /**
     * موظفون نشطون يمكن ربطهم بمركز تكلفة في المالية (قوائم اختيار، تكاليف عمالة، إلخ).
     * يحترم عزل المستأجر عبر النطاق العام على نموذج Employee.
     *
     * @param  array<string, mixed>  $extraColumns
     */
    public function employeesAvailableForCostCenterAssignment(
        ?int $tenantUserId = null,
        array $extraColumns = []
    ): Collection {
        $columns = array_unique(array_merge(
            ['id', 'code', 'name', 'department_id', 'cost_center_id', 'status'],
            $extraColumns
        ));

        return $this->activeEmployeesBaseQuery($tenantUserId)
            ->orderBy('name')
            ->get($columns);
    }

    /**
     * نفس المنطق مع إرجاع Builder للاستعلامات المخصصة (تصفية إضافية، ترقيم، …).
     */
    public function activeEmployeesForCostCenterQuery(?int $tenantUserId = null): Builder
    {
        return $this->activeEmployeesBaseQuery($tenantUserId)->orderBy('name');
    }

    private function activeEmployeesBaseQuery(?int $tenantUserId): Builder
    {
        $query = Employee::query()->where('status', 'active');

        if ($tenantUserId !== null) {
            $query->where('user_id', $tenantUserId);
        }

        return $query;
    }
}
