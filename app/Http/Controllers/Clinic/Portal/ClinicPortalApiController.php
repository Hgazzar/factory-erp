<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clinic\Portal;

use App\Http\Controllers\Controller;
use App\Models\Clinic\Appointment;
use App\Services\Clinic\ClinicAvailabilityService;
use App\Services\Clinic\ClinicPortalBookingService;
use App\Services\Clinic\ClinicSpecialtyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class ClinicPortalApiController extends Controller
{
    public function specialties(Request $request, ClinicSpecialtyService $specialties): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');

        return response()->json([
            'specialties' => $specialties->activeForPortal($tenantUserId),
        ]);
    }

    public function doctors(Request $request, ClinicSpecialtyService $specialties): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $specialtyId = $request->query('specialty_id');

        return response()->json([
            'doctors' => $specialties->doctorsForSpecialty(
                $tenantUserId,
                $specialtyId !== null && $specialtyId !== '' ? (int) $specialtyId : null,
            ),
        ]);
    }

    public function dates(Request $request, ClinicAvailabilityService $availability): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $doctorId = (int) $request->query('doctor_id', 0);

        if ($doctorId < 1) {
            return response()->json(['dates' => []]);
        }

        $lookahead = max(7, (int) config('clinic.portal.booking_lookahead_days', 30));
        $from = now()->startOfDay();
        $to = now()->addDays($lookahead)->endOfDay();

        return response()->json([
            'dates' => $availability->availableDates($tenantUserId, $doctorId, $from, $to, $lookahead),
        ]);
    }

    public function slots(Request $request, ClinicAvailabilityService $availability): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $doctorId = (int) $request->query('doctor_id', 0);
        $date = trim((string) $request->query('date', ''));

        if ($doctorId < 1 || $date === '') {
            return response()->json(['slots' => []]);
        }

        return response()->json([
            'slots' => $availability->availableSlotsForDate($tenantUserId, $doctorId, $date),
        ]);
    }

    public function book(Request $request, ClinicPortalBookingService $booking): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');

        $data = $request->validate([
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_phone' => ['required', 'string', 'max:32'],
            'doctor_employee_id' => ['required', 'integer', 'min:1'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string', 'max:8'],
        ]);

        try {
            $appointment = $booking->book($tenantUserId, $data);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'تم تأكيد حجزك بنجاح.',
            'appointment' => [
                'id' => $appointment->id,
                'number' => $appointment->appointment_number,
                'date' => $appointment->appointment_date?->format('Y-m-d'),
                'time' => substr((string) $appointment->start_time, 0, 5),
                'doctor' => $appointment->doctor?->name,
            ],
        ], 201);
    }

    public function cancel(Request $request, string $token, ClinicPortalBookingService $booking): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $appointment = $booking->findByManageToken($tenantUserId, $token);
        if ($appointment === null) {
            $appointmentId = (int) $request->input('appointment_id', 0);
            if ($appointmentId > 0) {
                $appointment = Appointment::withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($appointmentId)
                    ->first();
            }
        }

        if ($appointment === null) {
            return response()->json(['message' => 'رابط إدارة الموعد غير صالح.'], 404);
        }

        try {
            $booking->cancelFromPortal($appointment);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'تم إلغاء الموعد بنجاح.']);
    }

    public function reschedule(Request $request, string $token, ClinicPortalBookingService $booking): JsonResponse
    {
        $tenantUserId = (int) $request->attributes->get('clinic_portal_tenant_user_id');
        $appointment = $booking->findByManageToken($tenantUserId, $token);
        if ($appointment === null) {
            $appointmentId = (int) $request->input('appointment_id', 0);
            if ($appointmentId > 0) {
                $appointment = Appointment::withoutGlobalScopes()
                    ->where('user_id', $tenantUserId)
                    ->whereKey($appointmentId)
                    ->first();
            }
        }

        if ($appointment === null) {
            return response()->json(['message' => 'رابط إدارة الموعد غير صالح.'], 404);
        }

        $data = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string', 'max:8'],
        ]);

        try {
            $booking->rescheduleFromPortal($appointment, (string) $data['appointment_date'], (string) $data['start_time']);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'تمت إعادة الجدولة بنجاح.']);
    }
}
