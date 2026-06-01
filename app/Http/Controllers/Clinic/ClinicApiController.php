<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicAllergyAlertService;
use App\Services\Clinic\ClinicBillingService;
use App\Services\Clinic\ClinicMedicalAttachmentService;
use App\Services\Clinic\ClinicPatientTimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class ClinicApiController extends Controller
{
    use ResolvesOperationsTenant;

    public function quoteServices(Request $request, ClinicBillingService $billing): JsonResponse
    {
        $data = $request->validate([
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer'],
        ]);

        try {
            $quote = $billing->quote(
                $this->resolveOperationsTenantUserId(),
                array_map('intval', $data['service_ids']),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($quote);
    }

    public function checkAllergy(Request $request, ClinicAllergyAlertService $alerts): JsonResponse
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer'],
            'medication' => ['required', 'string', 'max:255'],
        ]);

        $patient = Patient::query()
            ->where('user_id', $this->resolveOperationsTenantUserId())
            ->findOrFail((int) $data['patient_id']);

        return response()->json([
            'alerts' => $alerts->checkMedication($patient, $data['medication']),
        ]);
    }

    public function uploadManualPrescription(
        Request $request,
        ClinicMedicalAttachmentService $attachments,
        ClinicPatientTimelineService $timeline,
    ): JsonResponse {
        $data = $request->validate([
            'patient_id' => ['required', 'integer'],
            'appointment_id' => ['nullable', 'integer'],
            'image' => ['required', 'file', 'image', 'max:15360'],
        ]);

        $tenantUserId = $this->resolveOperationsTenantUserId();

        $patient = Patient::query()
            ->where('user_id', $tenantUserId)
            ->findOrFail((int) $data['patient_id']);

        try {
            $attachment = $attachments->storeManualPrescriptionImage(
                $tenantUserId,
                $patient,
                $data['image'],
                isset($data['appointment_id']) ? (int) $data['appointment_id'] : null,
                (int) auth()->id(),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $event = $timeline->formatManualPrescriptionEvent($attachment);

        return response()->json([
            'message' => 'تم رفع الروشتة اليدوية بنجاح.',
            'attachment' => [
                'id' => $attachment->id,
                'original_name' => $attachment->original_name,
                'category' => $attachment->category,
                'preview_url' => route('clinic.attachments.preview', $attachment),
                'download_url' => route('clinic.attachments.download', $attachment),
                'appointment_id' => $attachment->clinic_appointment_id,
                'created_at' => $attachment->created_at?->toIso8601String(),
            ],
            'timeline_event' => $timeline->serializeEvent($event),
        ], 201);
    }
}
