<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShiftWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $filterStatus = $request->query('status', 'active');
        if (! in_array($filterStatus, ['active', 'inactive', 'all'], true)) {
            $filterStatus = 'active';
        }

        $query = Shift::query()->withCount('employees')->orderBy('code');

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', '%'.$s.'%')
                    ->orWhere('name_ar', 'like', '%'.$s.'%')
                    ->orWhere('name_en', 'like', '%'.$s.'%');
            });
        }

        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        }

        $shifts = $query->paginate(20)->withQueryString();

        return view('hr.shifts.index', compact('shifts', 'filterStatus'));
    }

    public function create(): View
    {
        return view('hr.shifts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate($this->shiftRules($tenantUserId));
        $data['user_id'] = $tenantUserId;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_night'] = $request->boolean('is_night', false);

        Shift::query()->create($data);

        return redirect()
            ->route('hr.shifts.index')
            ->with('success', 'تم إنشاء الوردية بنجاح.');
    }

    public function show(Shift $shift): View
    {
        $shift->loadCount(['employees', 'productionShifts']);

        return view('hr.shifts.show', compact('shift'));
    }

    public function edit(Shift $shift): View
    {
        return view('hr.shifts.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $data = $request->validate($this->shiftRules($tenantUserId, $shift));
        $data['is_active'] = $request->boolean('is_active');
        $data['is_night'] = $request->boolean('is_night');

        $shift->update($data);

        return redirect()
            ->route('hr.shifts.show', $shift)
            ->with('success', 'تم تحديث الوردية.');
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->employees()->exists()) {
            return back()->with('error', 'لا يمكن حذف وردية مرتبطة بموظفين.');
        }

        if ($shift->productionShifts()->exists()) {
            return back()->with('error', 'لا يمكن حذف وردية مستخدمة في ورديات الإنتاج.');
        }

        $shift->delete();

        return redirect()
            ->route('hr.shifts.index')
            ->with('success', 'تم حذف الوردية.');
    }

    /**
     * @return array<string, mixed>
     */
    private function shiftRules(int $tenantUserId, ?Shift $shift = null): array
    {
        $uniqueCode = Rule::unique('shifts', 'code')->where('user_id', $tenantUserId);
        if ($shift) {
            $uniqueCode->ignore($shift->id);
        }

        return [
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'is_night' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
