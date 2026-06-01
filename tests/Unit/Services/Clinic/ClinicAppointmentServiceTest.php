<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Clinic;

use App\Models\Clinic\Appointment;
use App\Models\Clinic\InsuranceCompany;
use App\Models\Clinic\InsurancePlan;
use App\Models\CompanySetting;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Services\Clinic\ClinicAppointmentService;
use App\Services\Clinic\ClinicPatientService;
use App\Support\DefaultLedgerAccounts;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ClinicTestCase;

final class ClinicAppointmentServiceTest extends ClinicTestCase
{
    private ClinicAppointmentService $appointments;

    private ClinicPatientService $patients;

    protected function setUp(): void
    {
        parent::setUp();

        $this->appointments = app(ClinicAppointmentService::class);
        $this->patients = app(ClinicPatientService::class);

        DefaultLedgerAccounts::salesRevenueForTenant((int) $this->tenant->id);
        DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', (int) $this->tenant->id);
    }

    #[Test]
    public function schedule_creates_pending_appointment_with_number(): void
    {
        $patient = $this->patients->create((int) $this->tenant->id, [
            'name' => 'سارة أحمد',
            'phone' => '01001234567',
        ]);

        $appointment = $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
        ]);

        $this->assertSame(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertStringStartsWith('CLN-', $appointment->appointment_number);
        $this->assertNull($appointment->paid_at);
    }

    #[Test]
    public function complete_with_payment_posts_revenue_journal(): void
    {
        $patient = $this->patients->create((int) $this->tenant->id, ['name' => 'محمد علي']);

        $appointment = $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '11:30',
        ]);

        $completed = $this->appointments->completeWithPayment($appointment, 500.0, 'cash');

        $this->assertSame(Appointment::STATUS_COMPLETED, $completed->status);
        $this->assertNotNull($completed->paid_at);
        $this->assertNotNull($completed->journal_entry_id);

        $entry = JournalEntry::query()->find($completed->journal_entry_id);
        $this->assertNotNull($entry);
        $this->assertEqualsWithDelta(500.0, (float) $entry->total, 0.01);
    }

    #[Test]
    public function cancel_prevents_complete_with_payment(): void
    {
        $patient = $this->patients->create((int) $this->tenant->id, ['name' => 'ليلى']);

        $appointment = $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '09:00',
        ]);

        $this->appointments->cancel($appointment);

        $this->expectException(\RuntimeException::class);
        $this->appointments->completeWithPayment($appointment->fresh(), 200.0);
    }

    #[Test]
    public function quick_schedule_creates_patient_and_appointment(): void
    {
        $appointment = $this->appointments->quickSchedule(
            (int) $this->tenant->id,
            ['name' => 'Quick Patient', 'phone' => '01009998877'],
            [
                'appointment_date' => now()->toDateString(),
                'start_time' => '14:00',
            ],
            $this->patients,
        );

        $this->assertDatabaseHas('patients', [
            'user_id' => $this->tenant->id,
            'name' => 'Quick Patient',
        ]);
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->status);
    }

    #[Test]
    public function reschedule_updates_date_and_time(): void
    {
        $patient = $this->patients->create((int) $this->tenant->id, ['name' => 'Reschedule Test']);

        $appointment = $this->appointments->schedule((int) $this->tenant->id, [
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '08:00',
        ]);

        $rescheduled = $this->appointments->reschedule(
            $appointment,
            now()->addDays(2)->toDateString(),
            '15:30',
        );

        $this->assertSame(now()->addDays(2)->toDateString(), $rescheduled->appointment_date->toDateString());
        $this->assertStringStartsWith('15:30', (string) $rescheduled->start_time);
    }

    #[Test]
    public function insured_patient_payment_splits_cash_and_receivable(): void
    {
        $tenantId = (int) $this->tenant->id;
        $cash = DefaultLedgerAccounts::paymentSourceAssetForTenant('cash', $tenantId);
        $ar = DefaultLedgerAccounts::accountsReceivableForTenant($tenantId);

        CompanySetting::query()->updateOrCreate(
            ['user_id' => $tenantId],
            ['default_receivable_account_id' => $ar->id],
        );

        $company = InsuranceCompany::query()->create([
            'user_id' => $tenantId,
            'code' => 'MEDG',
            'name' => 'Med Gulf',
            'is_active' => true,
        ]);

        $plan = InsurancePlan::query()->create([
            'user_id' => $tenantId,
            'clinic_insurance_company_id' => $company->id,
            'name' => 'Class A',
            'copay_percent' => 20,
            'max_copay_amount' => null,
            'is_active' => true,
        ]);

        $patient = $this->patients->create($tenantId, [
            'name' => 'مؤمن عليه',
            'clinic_insurance_company_id' => $company->id,
            'clinic_insurance_plan_id' => $plan->id,
            'insurance_card_number' => 'CARD-7788',
        ]);

        $appointment = $this->appointments->schedule($tenantId, [
            'patient_id' => $patient->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '12:00',
        ]);

        $completed = $this->appointments->completeWithPayment($appointment, 1000.0, 'cash');
        $entry = JournalEntry::query()->findOrFail((int) $completed->journal_entry_id);
        $items = JournalItem::query()->where('journal_entry_id', $entry->id)->get();

        $this->assertTrue($items->contains(fn (JournalItem $i) => (int) $i->account_id === (int) $cash->id && (float) $i->debit === 200.0));
        $this->assertTrue($items->contains(fn (JournalItem $i) => (int) $i->account_id === (int) $ar->id && (float) $i->debit === 800.0));
    }
}
