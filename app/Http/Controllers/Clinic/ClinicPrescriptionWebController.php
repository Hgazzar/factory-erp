<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Clinic\Prescription;
use App\Models\Employee;
use App\Services\Clinic\ClinicAllergyAlertService;
use App\Services\Clinic\ClinicPrescriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicPrescriptionWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $prescriptions = Prescription::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->where('user_id', $tenantUserId)
            ->orderByDesc('prescribed_at')
            ->paginate(20)
            ->withQueryString();

        return view('clinic.prescriptions.index', compact('prescriptions'));
    }

    public function create(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        $appointmentId = $request->integer('appointment_id') ?: null;
        $linkedAppointment = null;

        if ($appointmentId > 0) {
            $linkedAppointment = Appointment::query()
                ->with(['patient:id,name', 'doctor:id,name'])
                ->where('user_id', $tenantUserId)
                ->find($appointmentId);
        }

        $preselectedPatientId = $request->query('patient_id')
            ?? $linkedAppointment?->patient_id;

        $preselectedDoctorId = $request->query('doctor_employee_id')
            ?? $linkedAppointment?->doctor_employee_id;

        return view('clinic.prescriptions.create', [
            'patients' => Patient::query()->where('user_id', $tenantUserId)->orderBy('name')->get(['id', 'name']),
            'doctors' => $this->doctorOptions($tenantUserId),
            'preselectedPatientId' => $preselectedPatientId,
            'preselectedAppointmentId' => $linkedAppointment?->id,
            'preselectedDoctorId' => $preselectedDoctorId,
            'linkedAppointment' => $linkedAppointment,
            'returnTo' => $request->query('return_to'),
        ]);
    }

    public function store(Request $request, ClinicPrescriptionService $prescriptions, ClinicAllergyAlertService $allergyAlerts): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer'],
            'doctor_employee_id' => ['nullable', 'integer'],
            'clinic_appointment_id' => ['nullable', 'integer'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'medications' => ['required', 'array', 'min:1'],
            'medications.*.name' => ['required', 'string', 'max:255'],
            'medications.*.dosage' => ['nullable', 'string', 'max:128'],
            'medications.*.frequency' => ['nullable', 'string', 'max:128'],
            'medications.*.duration' => ['nullable', 'string', 'max:128'],
            'medications.*.notes' => ['nullable', 'string', 'max:500'],
            'return_to' => ['nullable', 'string', 'max:32'],
            'acknowledge_allergy_risk' => ['nullable', 'boolean'],
        ]);

        $tenantUserId = $this->resolveOperationsTenantUserId();

        $patient = Patient::query()
            ->where('user_id', $tenantUserId)
            ->findOrFail((int) $data['patient_id']);

        $medicationNames = array_map(
            static fn (array $row): string => (string) ($row['name'] ?? ''),
            $data['medications'],
        );

        $allergyAlerts = $allergyAlerts->checkMedications($patient, $medicationNames);

        if ($allergyAlerts !== [] && ! $request->boolean('acknowledge_allergy_risk')) {
            return back()
                ->withInput()
                ->withErrors([
                    'medications' => 'يوجد تعارض محتمل مع حساسيات المريض. راجع التحذيرات وأكّد المتابعة قبل الحفظ.',
                ])
                ->with('allergy_alerts', $allergyAlerts);
        }

        $prescription = $prescriptions->create(
            $tenantUserId,
            $data,
            (int) auth()->id(),
        );

        $returnTo = $request->input('return_to');

        if ($returnTo === 'appointments') {
            return redirect()
                ->route('clinic.appointments.index')
                ->with('success', 'تم حفظ الروشتة وربطها بالحجز بنجاح.');
        }

        return redirect()
            ->route('clinic.prescriptions.show', $prescription)
            ->with('success', 'تم حفظ الروشتة بنجاح.');
    }

    public function show(Prescription $prescription): View
    {
        $prescription->load(['patient', 'doctor', 'appointment']);

        return view('clinic.prescriptions.show', compact('prescription'));
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
