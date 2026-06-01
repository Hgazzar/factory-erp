<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\ClinicalNote;
use App\Models\Clinic\MedicalAttachment;
use App\Models\Clinic\Patient;
use App\Models\Clinic\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * الخط الزمني الموحّد لزيارات المريض.
 */
final class ClinicPatientTimelineService
{
    /**
     * @return Collection<int, array{type: string, date: Carbon, title: string, summary: string|null, id: int, meta: array<string, mixed>}>
     */
    public function build(Patient $patient): Collection
    {
        $events = collect();

        Appointment::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get()
            ->each(function (Appointment $appt) use ($events): void {
                $date = Carbon::parse($appt->appointment_date->format('Y-m-d').' '.substr((string) $appt->start_time, 0, 8));
                $events->push([
                    'type' => 'appointment',
                    'date' => $date,
                    'title' => 'حجز — '.$appt->appointment_number,
                    'summary' => Appointment::statusLabels()[$appt->status] ?? $appt->status,
                    'id' => (int) $appt->id,
                    'meta' => ['status' => $appt->status],
                ]);
            });

        Prescription::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('prescribed_at')
            ->get()
            ->each(function (Prescription $rx) use ($events): void {
                $events->push([
                    'type' => 'prescription',
                    'date' => $rx->prescribed_at ?? $rx->created_at,
                    'title' => 'روشتة ℞',
                    'summary' => $rx->diagnosis,
                    'id' => (int) $rx->id,
                    'meta' => ['medications_count' => count($rx->medications ?? [])],
                ]);
            });

        ClinicalNote::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('noted_at')
            ->get()
            ->each(function (ClinicalNote $note) use ($events): void {
                $events->push([
                    'type' => 'clinical_note',
                    'date' => $note->noted_at ?? $note->created_at,
                    'title' => 'ملخص زيارة',
                    'summary' => $note->diagnosis ?? $note->chief_complaint,
                    'id' => (int) $note->id,
                    'meta' => [],
                ]);
            });

        MedicalAttachment::query()
            ->where('patient_id', $patient->id)
            ->where('category', MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION)
            ->orderByDesc('created_at')
            ->get()
            ->each(function (MedicalAttachment $attachment) use ($events): void {
                $events->push($this->formatManualPrescriptionEvent($attachment));
            });

        return $events
            ->sortByDesc(fn (array $row) => $row['date']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @param  Collection<int, array{type: string, date: Carbon, title: string, summary: string|null, id: int, meta: array<string, mixed>}>  $timeline
     * @return list<array<string, mixed>>
     */
    public function serializeForFront(Collection $timeline): array
    {
        return $timeline
            ->map(fn (array $event): array => $this->serializeEvent($event))
            ->values()
            ->all();
    }

    /**
     * @param  array{type: string, date: Carbon, title: string, summary: string|null, id: int, meta: array<string, mixed>}  $event
     * @return array<string, mixed>
     */
    public function serializeEvent(array $event): array
    {
        $date = $event['date'];
        $type = $event['type'];

        return [
            'type' => $type,
            'date_label' => $date?->translatedFormat('d M Y — H:i'),
            'date_ts' => $date?->timestamp ?? 0,
            'title' => $event['title'],
            'summary' => $event['summary'],
            'id' => $event['id'],
            'meta' => $event['meta'],
            'dot_class' => match ($type) {
                'appointment' => 'bg-amber-400',
                'prescription' => 'bg-teal-500',
                'manual_prescription' => 'bg-violet-500',
                default => 'bg-indigo-500',
            },
        ];
    }

    /**
     * @return array{type: string, date: Carbon, title: string, summary: string|null, id: int, meta: array<string, mixed>}
     */
    public function formatManualPrescriptionEvent(MedicalAttachment $attachment): array
    {
        return [
            'type' => 'manual_prescription',
            'date' => $attachment->created_at ?? now(),
            'title' => 'روشتة يدوية مصورة 📸',
            'summary' => MedicalAttachment::categoryLabels()[MedicalAttachment::CATEGORY_MANUAL_PRESCRIPTION]
                .($attachment->original_name ? ' — '.$attachment->original_name : ''),
            'id' => (int) $attachment->id,
            'meta' => [
                'attachment_id' => (int) $attachment->id,
                'appointment_id' => $attachment->clinic_appointment_id,
                'preview_url' => route('clinic.attachments.preview', $attachment),
                'download_url' => route('clinic.attachments.download', $attachment),
                'mime_type' => $attachment->mime_type,
            ],
        ];
    }
}
