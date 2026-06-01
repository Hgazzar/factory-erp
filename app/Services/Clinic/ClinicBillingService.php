<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\AppointmentServiceLine;
use App\Models\Clinic\ClinicService;
use App\Models\CompanySetting;
use InvalidArgumentException;

/**
 * حساب إجمالي الخدمات وض.ق.م للحجز.
 */
final class ClinicBillingService
{
    /**
     * @param  list<int>  $serviceIds
     * @return array{lines: list<array{service_id: int, name: string, quantity: int, unit_price: float, vat_amount: float, line_total: float}>, subtotal: float, vat_total: float, grand_total: float}
     */
    public function quote(int $tenantUserId, array $serviceIds, ?array $quantities = null): array
    {
        $serviceIds = array_values(array_unique(array_map('intval', $serviceIds)));

        if ($serviceIds === []) {
            throw new InvalidArgumentException('اختر خدمة واحدة على الأقل.');
        }

        $services = ClinicService::query()
            ->where('user_id', $tenantUserId)
            ->where('is_active', true)
            ->whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');

        if ($services->count() !== count($serviceIds)) {
            throw new InvalidArgumentException('بعض الخدمات غير متاحة.');
        }

        $vatPct = CompanySetting::resolvedDefaultVatPercent($tenantUserId);
        $lines = [];
        $subtotal = 0.0;
        $vatTotal = 0.0;

        foreach ($serviceIds as $idx => $serviceId) {
            /** @var ClinicService $service */
            $service = $services->get($serviceId);
            $qty = max(1, (int) ($quantities[$idx] ?? $quantities[$serviceId] ?? 1));
            $price = round((float) $service->price, 4);

            if ($service->vat_inclusive && $vatPct > 0.00001) {
                $lineTotal = round($price * $qty, 4);
                $net = round($lineTotal / (1 + $vatPct / 100), 4);
                $vat = round($lineTotal - $net, 4);
            } else {
                $net = round($price * $qty, 4);
                $vat = $vatPct > 0.00001 ? round($net * $vatPct / 100, 4) : 0.0;
                $lineTotal = round($net + $vat, 4);
            }

            $subtotal += $net;
            $vatTotal += $vat;

            $lines[] = [
                'service_id' => (int) $service->id,
                'name' => $service->name,
                'quantity' => $qty,
                'unit_price' => $price,
                'vat_amount' => $vat,
                'line_total' => $lineTotal,
            ];
        }

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 4),
            'vat_total' => round($vatTotal, 4),
            'grand_total' => round($subtotal + $vatTotal, 4),
        ];
    }

    /**
     * @param  list<int>  $serviceIds
     */
    public function attachLinesToAppointment(Appointment $appointment, int $tenantUserId, array $serviceIds, ?array $quantities = null): array
    {
        $quote = $this->quote($tenantUserId, $serviceIds, $quantities);

        AppointmentServiceLine::query()
            ->where('clinic_appointment_id', $appointment->id)
            ->delete();

        foreach ($quote['lines'] as $line) {
            AppointmentServiceLine::query()->create([
                'user_id' => $tenantUserId,
                'clinic_appointment_id' => $appointment->id,
                'clinic_service_id' => $line['service_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'vat_amount' => $line['vat_amount'],
                'line_total' => $line['line_total'],
            ]);
        }

        return $quote;
    }
}
