<?php

declare(strict_types=1);

namespace App\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\Patient;
use App\Models\Clinic\InsurancePlan;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Services\FinancialRecordingService;
use App\Services\Tenant\TenantFeatureRegistry;
use App\Support\DefaultLedgerAccounts;
use InvalidArgumentException;
use RuntimeException;

/**
 * قيد إيراد كشف العيادة — يستخدم FinancialRecordingService المشترك.
 */
final class ClinicAccountingService
{
    public function __construct(
        private readonly FinancialRecordingService $financial,
    ) {}

    public function recordAppointmentPayment(Appointment $appointment, int $tenantUserId): JournalEntry
    {
        if ($appointment->journal_entry_id) {
            throw new RuntimeException('تم ترحيل قيد هذا الحجز مسبقاً.');
        }

        $fee = round((float) ($appointment->fee_amount ?? 0), 4);

        if ($fee <= 0) {
            throw new InvalidArgumentException('مبلغ الكشف يجب أن يكون أكبر من صفر.');
        }

        $paymentMethod = (string) ($appointment->payment_method ?: 'cash');
        $cashOrBank = DefaultLedgerAccounts::paymentSourceAssetForTenant($paymentMethod, $tenantUserId);
        $salesAccount = DefaultLedgerAccounts::salesRevenueForTenant($tenantUserId);
        $receivableAccountId = $this->resolveReceivableAccountId($tenantUserId);

        $pct = CompanySetting::resolvedDefaultVatPercent($tenantUserId);
        $netRev = $fee;
        $vatAmt = 0.0;

        if ($pct > 0.00001) {
            $netRev = round($fee / (1 + $pct / 100), 4);
            $vatAmt = round($fee - $netRev, 4);
            if ($vatAmt < 0.0001) {
                $vatAmt = 0.0;
                $netRev = $fee;
            }
        }

        $split = $this->resolveInsuranceSplit($appointment, $fee);
        $cashPart = $split['cash'];
        $insurancePart = $split['insurance'];

        $lines = [];

        if ($cashPart > 0.0001) {
            $lines[] = [
                'account_id' => (int) $cashOrBank->id,
                'debit' => $cashPart,
                'credit' => 0,
                'description' => 'تحصيل نقدي من المريض — '.$appointment->appointment_number,
            ];
        }

        if ($insurancePart > 0.0001) {
            $lines[] = [
                'account_id' => $receivableAccountId,
                'debit' => $insurancePart,
                'credit' => 0,
                'description' => 'ذمم شركة التأمين — '.$appointment->appointment_number,
            ];
        }

        $lines[] = [
            'account_id' => (int) $salesAccount->id,
            'debit' => 0,
            'credit' => $netRev,
            'description' => 'إيراد كشف عيادة',
        ];

        if ($vatAmt > 0.0001) {
            $vatAccount = DefaultLedgerAccounts::vatPayableForTenant($tenantUserId);
            $lines[] = [
                'account_id' => (int) $vatAccount->id,
                'debit' => 0,
                'credit' => $vatAmt,
                'description' => 'ضريبة قيمة مضافة — كشف عيادة',
            ];
        }

        $patientName = $appointment->relationLoaded('patient')
            ? ($appointment->patient?->name ?? '')
            : Patient::withoutGlobalScopes()->find($appointment->patient_id)?->name ?? '';

        return $this->financial->recordBalancedJournal(
            $tenantUserId,
            $appointment->appointment_date?->format('Y-m-d') ?? now()->toDateString(),
            $appointment->appointment_number,
            'كشف عيادة — '.$patientName,
            $lines,
            (int) ($appointment->created_by ?? $tenantUserId),
        );
    }

    /**
     * @return array{cash: float, insurance: float}
     */
    private function resolveInsuranceSplit(Appointment $appointment, float $fee): array
    {
        if (! app(TenantFeatureRegistry::class)->isEnabled('clinic_medical_insurance', (int) $appointment->user_id)) {
            return ['cash' => $fee, 'insurance' => 0.0];
        }

        $patient = $appointment->relationLoaded('patient')
            ? $appointment->patient
            : Patient::withoutGlobalScopes()->find($appointment->patient_id);

        if ($patient === null || $patient->clinic_insurance_plan_id === null) {
            return ['cash' => $fee, 'insurance' => 0.0];
        }

        /** @var InsurancePlan|null $plan */
        $plan = $patient->relationLoaded('insurancePlan')
            ? $patient->insurancePlan
            : InsurancePlan::withoutGlobalScopes()->find($patient->clinic_insurance_plan_id);

        if ($plan === null || ! $plan->is_active) {
            return ['cash' => $fee, 'insurance' => 0.0];
        }

        $copayPercent = max(0.0, min(100.0, (float) $plan->copay_percent));
        $cash = round($fee * ($copayPercent / 100), 4);

        if ($plan->max_copay_amount !== null) {
            $cash = min($cash, round((float) $plan->max_copay_amount, 4));
        }

        $cash = max(0.0, min($cash, $fee));
        $insurance = round($fee - $cash, 4);

        return ['cash' => $cash, 'insurance' => $insurance];
    }

    private function resolveReceivableAccountId(int $tenantUserId): int
    {
        $company = CompanySetting::forTenant($tenantUserId);

        if ($company?->default_receivable_account_id) {
            return (int) $company->default_receivable_account_id;
        }

        return (int) DefaultLedgerAccounts::accountsReceivableForTenant($tenantUserId)->id;
    }
}
