<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicPatientService;
use App\Services\Clinic\ClinicPatientTimelineService;
use App\Services\Clinic\ClinicPhiAuditService;
use App\Services\Clinic\ClinicServiceCatalogService;
use App\Support\ClinicAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ClinicPatientWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function __construct(
        private readonly ClinicPhiAuditService $audit,
        private readonly ClinicAccess $clinicAccess,
    ) {}

    public function index(Request $request): View
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();
        $q = trim((string) $request->query('q', ''));

        $patients = Patient::query()
            ->where('user_id', $tenantUserId)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('national_id', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('clinic.patients.index', [
            'patients' => $patients,
            'bloodTypeOptions' => $this->bloodTypeOptions(),
        ]);
    }

    public function show(Patient $patient, ClinicPatientTimelineService $timelineService): View
    {
        if ($this->clinicAccess->allows(ClinicAccess::CAP_VIEW_CLINICAL)) {
            $this->audit->logPatientView($patient);
            $patient->load(['medicalAttachments', 'clinicalNotes' => fn ($q) => $q->orderByDesc('noted_at')->limit(5)]);

            $timeline = $timelineService->build($patient);

            return view('clinic.patients.show', [
                'patient' => $patient,
                'timeline' => $timeline,
                'timelineEvents' => $timelineService->serializeForFront($timeline),
                'clinicalMode' => true,
            ]);
        }

        return view('clinic.patients.show-limited', [
            'patient' => $patient,
            'clinicalMode' => false,
        ]);
    }

    public function store(Request $request, ClinicPatientService $patients): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:64'],
            'blood_type' => ['nullable', 'string', 'max:8'],
            'clinic_insurance_company_id' => ['nullable', 'integer'],
            'clinic_insurance_plan_id' => ['nullable', 'integer'],
            'insurance_card_number' => ['nullable', 'string', 'max:64'],
            'insurance_expires_at' => ['nullable', 'date'],
            'medical_history_summary' => ['nullable', 'string', 'max:5000'],
            'allergies' => ['nullable', 'string', 'max:5000'],
            'chronic_conditions' => ['nullable', 'string', 'max:5000'],
        ]);

        $patients->create($this->resolveOperationsTenantUserId(), $data, (int) auth()->id());

        return back()->with('success', 'تم إضافة المريض بنجاح.');
    }

    public function updateClinical(Request $request, Patient $patient, ClinicPatientService $patients): RedirectResponse
    {
        $data = $request->validate([
            'allergies' => ['nullable', 'string', 'max:5000'],
            'chronic_conditions' => ['nullable', 'string', 'max:5000'],
            'medical_history_summary' => ['nullable', 'string', 'max:5000'],
            'blood_type' => ['nullable', 'string', 'max:8'],
        ]);

        $patients->update($patient, $data);
        $this->audit->logPatientView($patient, 'clinical_profile_updated');

        return back()->with('success', 'تم تحديث الملف الطبي.');
    }

    public function edit(Patient $patient): View
    {
        return view('clinic.patients.edit', [
            'patient' => $patient,
            'bloodTypeOptions' => $this->bloodTypeOptions(),
            'canClinical' => $this->clinicAccess->allows(ClinicAccess::CAP_VIEW_CLINICAL),
        ]);
    }

    public function update(Request $request, Patient $patient, ClinicPatientService $patients): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:64'],
            'blood_type' => ['nullable', 'string', 'max:8'],
            'clinic_insurance_company_id' => ['nullable', 'integer'],
            'clinic_insurance_plan_id' => ['nullable', 'integer'],
            'insurance_card_number' => ['nullable', 'string', 'max:64'],
            'insurance_expires_at' => ['nullable', 'date'],
        ];

        if ($this->clinicAccess->allows(ClinicAccess::CAP_VIEW_CLINICAL)) {
            $rules['medical_history_summary'] = ['nullable', 'string', 'max:5000'];
            $rules['allergies'] = ['nullable', 'string', 'max:5000'];
            $rules['chronic_conditions'] = ['nullable', 'string', 'max:5000'];
        }

        $data = $request->validate($rules);

        $patients->update($patient, $data);

        if ($this->clinicAccess->allows(ClinicAccess::CAP_VIEW_CLINICAL)) {
            $this->audit->logPatientView($patient, 'patient_profile_updated');
        }

        return redirect()
            ->route('clinic.patients.show', $patient)
            ->with('success', 'تم تحديث بيانات المريض بنجاح.');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function bloodTypeOptions(): array
    {
        return collect(Patient::BLOOD_TYPES)
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => $type === 'unknown' ? 'غير محدد' : $type,
            ])
            ->all();
    }
}
