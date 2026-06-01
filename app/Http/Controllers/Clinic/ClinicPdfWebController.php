<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\Appointment;
use App\Models\Clinic\Prescription;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ClinicPdfWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function receipt(Appointment $appointment): Response
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        if ((int) $appointment->user_id !== $tenantUserId) {
            abort(404);
        }

        if (! $appointment->isPaid()) {
            abort(422, 'لا يمكن طباعة إيصال لحجز لم يُحصَّل بعد.');
        }

        $appointment->load([
            'patient:id,name,phone,code',
            'doctor:id,name',
            'serviceLines.service:id,name',
        ]);

        $company = CompanySetting::forTenant($tenantUserId);
        $logoDataUri = $this->resolveLogoDataUri($company?->logo_url);

        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $appointment->appointment_number);
        $filename = 'clinic-receipt-'.$safe.'.pdf';

        try {
            return Pdf::loadView('clinic.pdf.receipt', [
                'appointment' => $appointment,
                'company' => $company,
                'logoDataUri' => $logoDataUri,
            ])
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false)
                ->setOption('isHtml5ParserEnabled', true)
                ->stream($filename);
        } catch (\Throwable $e) {
            Log::error('Clinic receipt PDF failed', [
                'appointment_id' => $appointment->id,
                'message' => $e->getMessage(),
            ]);

            abort(500, 'تعذّر إنشاء إيصال PDF.');
        }
    }

    public function prescription(Prescription $prescription): Response
    {
        $tenantUserId = $this->resolveOperationsTenantUserId();

        if ((int) $prescription->user_id !== $tenantUserId) {
            abort(404);
        }

        $prescription->load(['patient:id,name,phone,code', 'doctor:id,name,job_title']);

        $company = CompanySetting::forTenant($tenantUserId);
        $logoDataUri = $this->resolveLogoDataUri($company?->logo_url);

        $filename = 'prescription-'.$prescription->id.'.pdf';

        try {
            return Pdf::loadView('clinic.pdf.prescription', [
                'prescription' => $prescription,
                'company' => $company,
                'logoDataUri' => $logoDataUri,
            ])
                ->setPaper('a4', 'portrait')
                ->setOption('isRemoteEnabled', false)
                ->setOption('isHtml5ParserEnabled', true)
                ->stream($filename);
        } catch (\Throwable $e) {
            Log::error('Clinic prescription PDF failed', [
                'prescription_id' => $prescription->id,
                'message' => $e->getMessage(),
            ]);

            abort(500, 'تعذّر إنشاء روشتة PDF.');
        }
    }

    private function resolveLogoDataUri(?string $logoPath): ?string
    {
        if ($logoPath === null || ! str_starts_with($logoPath, 'company/')) {
            return null;
        }

        if (! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';

        if (! is_string($mime) || ! str_starts_with($mime, 'image/')) {
            return null;
        }

        $bytes = Storage::disk('public')->get($logoPath);

        if ($bytes === false || $bytes === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }
}
