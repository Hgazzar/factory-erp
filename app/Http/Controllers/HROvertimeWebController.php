<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HROvertimeWebController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manage_payroll');

        $uid = (int) $request->user()->id;
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $pendingCount = (int) OvertimeRequest::query()
            ->where('user_id', $uid)
            ->where('status', OvertimeRequest::STATUS_NEW)
            ->count();

        $totalHoursMonth = (float) OvertimeRequest::query()
            ->where('user_id', $uid)
            ->whereBetween('work_date', [$monthStart, $monthEnd])
            ->sum('hours');

        $approvedHoursMonth = (float) OvertimeRequest::query()
            ->where('user_id', $uid)
            ->where('status', OvertimeRequest::STATUS_APPROVED)
            ->whereBetween('work_date', [$monthStart, $monthEnd])
            ->sum('hours');

        $statusFilter = (string) $request->query('status', '');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = OvertimeRequest::query()
            ->with(['employee'])
            ->orderByDesc('work_date')
            ->orderByDesc('id');

        if ($statusFilter !== '' && in_array($statusFilter, [
            OvertimeRequest::STATUS_NEW,
            OvertimeRequest::STATUS_APPROVED,
            OvertimeRequest::STATUS_REJECTED,
        ], true)) {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('work_date', '>=', Carbon::parse($dateFrom)->toDateString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('work_date', '<=', Carbon::parse($dateTo)->toDateString());
        }

        $requests = $query->paginate(20)->withQueryString();

        $statusOptions = [
            ['value' => '', 'label' => 'كل الحالات'],
            ['value' => OvertimeRequest::STATUS_NEW, 'label' => 'جديد'],
            ['value' => OvertimeRequest::STATUS_APPROVED, 'label' => 'معتمد'],
            ['value' => OvertimeRequest::STATUS_REJECTED, 'label' => 'مرفوض'],
        ];

        return view('hr.overtime', compact(
            'requests',
            'statusOptions',
            'pendingCount',
            'totalHoursMonth',
            'approvedHoursMonth',
            'statusFilter',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(Request $request): View
    {
        Gate::authorize('manage_payroll');

        $uid = (int) $request->user()->id;

        $employeeOptions = $this->employeeSelectOptions($uid);
        $kindOptions = [
            ['value' => OvertimeRequest::KIND_REGULAR, 'label' => 'عادي'],
            ['value' => OvertimeRequest::KIND_HOLIDAY, 'label' => 'عطلة'],
            ['value' => OvertimeRequest::KIND_FULL_DAY, 'label' => 'يوم كامل'],
        ];

        return view('hr.overtime.create', compact('employeeOptions', 'kindOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('manage_payroll');

        $uid = (int) $request->user()->id;

        $request->merge([
            'time_start' => $this->trimTimeToHi($request->input('time_start')),
            'time_end' => $this->trimTimeToHi($request->input('time_end')),
        ]);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('user_id', $uid)],
            'work_date' => ['required', 'date'],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i'],
            'kind' => ['required', 'string', Rule::in([
                OvertimeRequest::KIND_REGULAR,
                OvertimeRequest::KIND_HOLIDAY,
                OvertimeRequest::KIND_FULL_DAY,
            ])],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $hours = $this->computeDurationHours($data['time_start'], $data['time_end']);
        if ($hours < 0.01 || $hours > 24) {
            throw ValidationException::withMessages([
                'time_end' => 'مدة العمل الإضافي يجب أن تكون بين دقيقة و٢٤ ساعة (يُحسب تخطي منتصف الليل تلقائياً).',
            ]);
        }

        OvertimeRequest::query()->create([
            'user_id' => $uid,
            'employee_id' => (int) $data['employee_id'],
            'work_date' => Carbon::parse($data['work_date'])->toDateString(),
            'kind' => $data['kind'],
            'time_start' => $this->normalizeTimeForStorage($data['time_start']),
            'time_end' => $this->normalizeTimeForStorage($data['time_end']),
            'hours' => $hours,
            'reason' => $data['reason'],
            'status' => OvertimeRequest::STATUS_NEW,
            'rate_multiplier' => OvertimeRequest::rateMultiplierForKind($data['kind']),
        ]);

        return redirect()
            ->route('hr.overtime')
            ->with('success', 'تم تسجيل طلب العمل الإضافي، وهو بانتظار الاعتماد.');
    }

    public function approve(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        Gate::authorize('manage_payroll');

        if ($overtimeRequest->status !== OvertimeRequest::STATUS_NEW) {
            return back()->with('error', 'يمكن اعتماد الطلبات في حالة «جديد» فقط.');
        }

        $overtimeRequest->update([
            'status' => OvertimeRequest::STATUS_APPROVED,
            'approved_by' => (int) $request->user()->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        return back()->with('success', 'تم اعتماد طلب العمل الإضافي.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        Gate::authorize('manage_payroll');

        if ($overtimeRequest->status !== OvertimeRequest::STATUS_NEW) {
            return back()->with('error', 'يمكن رفض الطلبات في حالة «جديد» فقط.');
        }

        $overtimeRequest->update([
            'status' => OvertimeRequest::STATUS_REJECTED,
            'rejected_by' => (int) $request->user()->id,
            'rejected_at' => now(),
        ]);

        return back()->with('success', 'تم رفض طلب العمل الإضافي.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function employeeSelectOptions(int $uid): array
    {
        return Employee::query()
            ->where('user_id', $uid)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(500)
            ->get()
            ->map(fn (Employee $e) => [
                'value' => (string) $e->id,
                'label' => trim(($e->code ? $e->code.' — ' : '').$e->name),
            ])
            ->values()
            ->all();
    }

    private function trimTimeToHi(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    }

    private function normalizeTimeForStorage(string $hmsOrHm): string
    {
        $hmsOrHm = trim($hmsOrHm);

        return strlen($hmsOrHm) === 5 ? $hmsOrHm.':00' : $hmsOrHm;
    }

    private function computeDurationHours(string $timeStart, string $timeEnd): float
    {
        $s = Carbon::parse('2000-01-01 '.$this->normalizeTimeForStorage($timeStart));
        $e = Carbon::parse('2000-01-01 '.$this->normalizeTimeForStorage($timeEnd));
        if ($e->lte($s)) {
            $e->addDay();
        }

        return round($s->diffInMinutes($e) / 60, 2);
    }
}
