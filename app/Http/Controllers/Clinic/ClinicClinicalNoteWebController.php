<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\ClinicalNote;
use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicClinicalNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ClinicClinicalNoteWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function store(Request $request, Patient $patient, ClinicClinicalNoteService $notes): RedirectResponse
    {
        $data = $request->validate([
            'clinic_appointment_id' => ['nullable', 'integer'],
            'doctor_employee_id' => ['nullable', 'integer'],
            'chief_complaint' => ['nullable', 'string', 'max:5000'],
            'examination' => ['nullable', 'string', 'max:10000'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
        ]);

        $notes->create(
            $this->resolveOperationsTenantUserId(),
            ['patient_id' => $patient->id, ...$data],
            (int) auth()->id(),
        );

        return back()->with('success', 'تم حفظ الملخص السريري.');
    }

    public function update(Request $request, ClinicalNote $clinicalNote, ClinicClinicalNoteService $notes): RedirectResponse
    {
        $data = $request->validate([
            'chief_complaint' => ['nullable', 'string', 'max:5000'],
            'examination' => ['nullable', 'string', 'max:10000'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
        ]);

        $notes->update($clinicalNote, $data);

        return back()->with('success', 'تم تحديث الملخص السريري.');
    }
}
