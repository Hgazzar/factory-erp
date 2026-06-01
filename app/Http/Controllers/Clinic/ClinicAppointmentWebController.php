<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Employee;
use App\Services\Clinic\ClinicAppointmentService;
use App\Services\Clinic\ClinicPatientService;
use App\Services\Clinic\ClinicServiceCatalogService;
use App\Support\ClinicAccess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicAppointmentWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request, ClinicAppointmentService $service, ClinicServiceCatalogService $catalog): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $catalog->seedDefaults($tenantUserId);
        $viewMode = $request->query('view', 'week') === 'day' ? 'day' : 'week';

        $anchor = $request->query('date')
            ? Carbon::parse((string) $request->query('date'))
            : now();

        if ($viewMode === 'day') {
            $from = $anchor->copy()->startOfDay();
            $to = $anchor->copy()->endOfDay();
        } else {
            $from = $anchor->copy()->startOfWeek(Carbon::SATURDAY);
            $to = $anchor->copy()->endOfWeek(Carbon::FRIDAY);
        }

        $appointments = $service->forDateRange($tenantUserId, $from, $to);

        $days = collect();
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $days->push($cursor->copy());
            $cursor->addDay();
        }

        $timeSlots = collect();
        for ($hour = 8; $hour <= 20; $hour++) {
            $timeSlots->push(sprintf('%02d:00', $hour));
            if ($hour < 20) {
                $timeSlots->push(sprintf('%02d:30', $hour));
            }
        }

        return view('clinic.appointments.board', [
            'appointments' => $appointments,
            'days' => $days,
            'timeSlots' => $timeSlots,
            'viewMode' => $viewMode,
            'anchor' => $anchor,
            'doctors' => $this->doctorOptions($tenantUserId),
            'patients' => Patient::query()->where('user_id', $tenantUserId)->orderBy('name')->limit(200)->get(['id', 'name', 'phone']),
            'statusLabels' => Appointment::statusLabels(),
            'clinicServices' => $catalog->activeForTenant($tenantUserId),
            'canCollect' => app(ClinicAccess::class)->allows(ClinicAccess::CAP_COLLECT_PAYMENT),
            'canClinical' => app(ClinicAccess::class)->allows(ClinicAccess::CAP_VIEW_CLINICAL),
        ]);
    }

    public function store(Request $request, ClinicAppointmentService $appointments): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer'],
            'doctor_employee_id' => ['nullable', 'integer'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'string', 'max:8'],
            'end_time' => ['nullable', 'string', 'max:8'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $appointments->schedule(
                $this->resolveOperationsTenantUserId(),
                $data,
                (int) auth()->id(),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['start_time' => $e->getMessage()]);
        }

        return back()->with('success', 'تم حجز الموعد بنجاح.');
    }

    public function quickStore(Request $request, ClinicAppointmentService $appointments, ClinicPatientService $patients): RedirectResponse
    {
        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['nullable', 'string', 'max:32'],
            'doctor_employee_id' => ['nullable', 'integer'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'string', 'max:8'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $appointments->quickSchedule(
                $this->resolveOperationsTenantUserId(),
                [
                    'name' => $data['patient_name'],
                    'phone' => $data['patient_phone'] ?? null,
                ],
                [
                    'doctor_employee_id' => $data['doctor_employee_id'] ?? null,
                    'appointment_date' => $data['appointment_date'],
                    'start_time' => $data['start_time'],
                    'notes' => $data['notes'] ?? null,
                ],
                $patients,
                (int) auth()->id(),
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['start_time' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إنشاء المريض والحجز بنجاح.');
    }

    public function updateStatus(Request $request, Appointment $appointment, ClinicAppointmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:cancel,pending,complete_paid,reschedule'],
            'fee_amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank,card'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer'],
            'appointment_date' => ['required_if:action,reschedule', 'nullable', 'date'],
            'start_time' => ['required_if:action,reschedule', 'nullable', 'string', 'max:8'],
            'end_time' => ['nullable', 'string', 'max:8'],
        ]);

        if ($data['action'] === 'complete_paid' && ! app(ClinicAccess::class)->allows(ClinicAccess::CAP_COLLECT_PAYMENT)) {
            abort(403, 'ليس لديك صلاحية التحصيل.');
        }

        $serviceIds = isset($data['service_ids']) ? array_map('intval', $data['service_ids']) : null;

        try {
            match ($data['action']) {
                'cancel' => $service->cancel($appointment),
                'pending' => $service->markPending($appointment),
                'reschedule' => $service->reschedule(
                    $appointment,
                    (string) $data['appointment_date'],
                    (string) $data['start_time'],
                    isset($data['end_time']) ? (string) $data['end_time'] : null,
                ),
                'complete_paid' => $service->completeWithPayment(
                    $appointment,
                    (float) ($data['fee_amount'] ?? 0),
                    (string) ($data['payment_method'] ?? 'cash'),
                    $serviceIds !== [] ? $serviceIds : null,
                ),
            };
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['start_time' => $e->getMessage()]);
        }

        $message = match ($data['action']) {
            'cancel' => 'تم إلغاء الموعد.',
            'reschedule' => 'تم إعادة جدولة الموعد.',
            'complete_paid' => 'تم التحصيل بنجاح.',
            default => 'تم تحديث حالة الحجز.',
        };

        if ($data['action'] === 'complete_paid') {
            return back()
                ->with('success', $message)
                ->with('receipt_appointment_id', $appointment->id);
        }

        return back()->with('success', $message);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function doctorOptions(int $tenantUserId): array
    {
        return Employee::query()
            ->where('user_id', $tenantUserId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'job_title'])
            ->map(fn (Employee $e) => [
                'value' => (string) $e->id,
                'label' => trim($e->name.($e->job_title ? ' — '.$e->job_title : '')),
            ])
            ->all();
    }
}
