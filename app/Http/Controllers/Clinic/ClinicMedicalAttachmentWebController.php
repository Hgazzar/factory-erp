<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesOperationsTenant;
use App\Models\Clinic\MedicalAttachment;
use App\Models\Clinic\Patient;
use App\Services\Clinic\ClinicMedicalAttachmentService;
use App\Services\Clinic\ClinicPhiAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClinicMedicalAttachmentWebController extends Controller
{
    use ResolvesOperationsTenant;

    public function store(Request $request, Patient $patient, ClinicMedicalAttachmentService $attachments): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:15360'],
            'category' => ['nullable', 'string', 'max:32'],
        ]);

        $attachments->store(
            $this->resolveOperationsTenantUserId(),
            $patient,
            $data['file'],
            (string) ($data['category'] ?? MedicalAttachment::CATEGORY_OTHER),
            (int) auth()->id(),
        );

        return back()->with('success', 'تم رفع المرفق الطبي بنجاح.');
    }

    public function download(MedicalAttachment $medicalAttachment, ClinicMedicalAttachmentService $attachments, ClinicPhiAuditService $audit): StreamedResponse
    {
        $medicalAttachment->load('patient');
        $audit->logPatientView($medicalAttachment->patient, 'medical_attachment_download');

        $contents = $attachments->decryptContents($medicalAttachment);

        return response()->streamDownload(
            function () use ($contents): void {
                echo $contents;
            },
            $medicalAttachment->original_name,
            ['Content-Type' => $medicalAttachment->mime_type ?? 'application/octet-stream'],
        );
    }

    public function preview(MedicalAttachment $medicalAttachment, ClinicMedicalAttachmentService $attachments, ClinicPhiAuditService $audit): Response
    {
        $medicalAttachment->load('patient');
        $audit->logPatientView($medicalAttachment->patient, 'medical_attachment_preview');

        if (! $medicalAttachment->isImage()) {
            abort(415, 'المعاينة متاحة للصور فقط.');
        }

        $contents = $attachments->decryptContents($medicalAttachment);

        return response($contents, 200, [
            'Content-Type' => $medicalAttachment->mime_type ?? 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.addslashes($medicalAttachment->original_name).'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroy(MedicalAttachment $medicalAttachment, ClinicMedicalAttachmentService $attachments): RedirectResponse
    {
        $attachments->delete($medicalAttachment);

        return back()->with('success', 'تم حذف المرفق.');
    }
}
