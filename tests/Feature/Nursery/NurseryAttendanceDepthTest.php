<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Employee;
use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Classroom;
use App\Models\Nursery\Enrollment;
use App\Models\Nursery\Guardian;
use App\Models\Nursery\NurseryShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryAttendanceDepthTest extends NurseryTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function individual_check_in_and_check_out_keep_independent_times(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'آدم', '0502000001');

        Carbon::setTestNow(Carbon::parse('2026-08-20 08:12:00', config('app.timezone')));
        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect();

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('حاضر 08:12')
            ->assertSee('انصراف');

        Carbon::setTestNow(Carbon::parse('2026-08-20 14:05:00', config('app.timezone')));
        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.check-out'), ['child_id' => $child->id])
            ->assertRedirect();

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('انصرف 14:05')
            ->assertSee('تم الانصراف');

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'status' => AttendanceLog::STATUS_PRESENT,
        ]);
        $this->assertSame(1, AttendanceLog::query()->where('child_id', $child->id)->count());
    }

    #[Test]
    public function bulk_check_in_creates_independent_logs_and_skips_already_present(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $a = $this->makeEnrolledChild($room, 'ليان', '0502000002');
        $b = $this->makeEnrolledChild($room, 'جنى', '0502000003');
        $c = $this->makeEnrolledChild($room, 'فهد', '0502000004');

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $a->id])->assertRedirect();

        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.bulk-check-in'), [
                'classroom_id' => $room->id,
                'child_ids' => [$a->id, $b->id, $c->id],
            ])
            ->assertRedirect();

        $this->assertSame(3, AttendanceLog::query()->where('user_id', $this->tenant->id)->count());
        $this->assertNotNull(AttendanceLog::query()->where('child_id', $b->id)->value('checked_in_at'));
        $this->assertNotNull(AttendanceLog::query()->where('child_id', $c->id)->value('checked_in_at'));
    }

    #[Test]
    public function partial_bulk_check_in_only_affects_selected_children(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $a = $this->makeEnrolledChild($room, 'نورة', '0502000005');
        $b = $this->makeEnrolledChild($room, 'سارة', '0502000006');
        $c = $this->makeEnrolledChild($room, 'هدى', '0502000007');

        $this->post(route('nursery.attendance.bulk-check-in'), [
            'classroom_id' => $room->id,
            'child_ids' => [$a->id, $c->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', ['child_id' => $a->id]);
        $this->assertDatabaseHas('nursery_attendance_logs', ['child_id' => $c->id]);
        $this->assertDatabaseMissing('nursery_attendance_logs', ['child_id' => $b->id]);
    }

    #[Test]
    public function bulk_check_out_only_for_present_children(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $present = $this->makeEnrolledChild($room, 'حاضر', '0502000008');
        $waiting = $this->makeEnrolledChild($room, 'منتظر', '0502000009');
        $left = $this->makeEnrolledChild($room, 'منصرف', '0502000010');

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $present->id]);
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $left->id]);
        $this->post(route('nursery.attendance.check-out'), ['child_id' => $left->id]);

        $this->post(route('nursery.attendance.bulk-check-out'), [
            'classroom_id' => $room->id,
            'child_ids' => [$present->id, $waiting->id, $left->id],
        ])->assertRedirect();

        $this->assertNotNull(AttendanceLog::query()->where('child_id', $present->id)->value('checked_out_at'));
        $this->assertDatabaseMissing('nursery_attendance_logs', ['child_id' => $waiting->id]);
        $this->assertNotNull(AttendanceLog::query()->where('child_id', $left->id)->value('checked_out_at'));
        $this->assertSame(2, AttendanceLog::query()->where('user_id', $this->tenant->id)->count());
    }

    #[Test]
    public function bulk_skips_archived_children_and_other_classrooms(): void
    {
        [$roomA, $roomB] = $this->makeTwoClassrooms();
        $active = $this->makeEnrolledChild($roomA, 'نشط', '0502000011');
        $archived = $this->makeEnrolledChild($roomA, 'مؤرشف', '0502000012');
        $archived->forceFill(['status' => Child::STATUS_INACTIVE])->save();
        $other = $this->makeEnrolledChild($roomB, 'فصل آخر', '0502000013');

        $this->post(route('nursery.attendance.bulk-check-in'), [
            'classroom_id' => $roomA->id,
            'child_ids' => [$active->id, $archived->id, $other->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', ['child_id' => $active->id]);
        $this->assertDatabaseMissing('nursery_attendance_logs', ['child_id' => $archived->id]);
        $this->assertDatabaseMissing('nursery_attendance_logs', ['child_id' => $other->id]);
    }

    #[Test]
    public function archived_child_cannot_check_in_individually(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'مؤرشف فردي', '0502000014');
        $child->forceFill(['status' => Child::STATUS_INACTIVE])->save();

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    #[Test]
    public function tenant_isolation_skips_foreign_child_in_bulk_and_blocks_foreign_correction(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $ours = $this->makeEnrolledChild($room, 'طفلنا', '0502000015');

        $otherTenant = User::factory()->create(['role' => 'admin']);
        $foreignGuardian = Guardian::query()->create([
            'user_id' => (int) $otherTenant->id,
            'name' => 'ولي خارجي',
            'phone' => '0502999999',
        ]);
        $foreignChild = Child::query()->create([
            'user_id' => (int) $otherTenant->id,
            'name' => 'طفل خارجي',
            'guardian_id' => $foreignGuardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);
        $foreignLog = AttendanceLog::query()->create([
            'user_id' => (int) $otherTenant->id,
            'child_id' => $foreignChild->id,
            'attendance_date' => now()->toDateString(),
            'checked_in_at' => now(),
            'status' => AttendanceLog::STATUS_PRESENT,
        ]);

        $this->post(route('nursery.attendance.bulk-check-in'), [
            'classroom_id' => $room->id,
            'child_ids' => [$ours->id, $foreignChild->id],
        ])->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'user_id' => $this->tenant->id,
            'child_id' => $ours->id,
        ]);
        $this->assertSame(1, AttendanceLog::withoutGlobalScopes()->where('child_id', $foreignChild->id)->count());

        $this->patch(route('nursery.attendance.correct', $foreignLog), [
            'checked_in_at' => '08:00',
            'status' => AttendanceLog::STATUS_PRESENT,
        ])->assertForbidden();
    }

    #[Test]
    public function view_only_cannot_bulk_or_correct(): void
    {
        $staff = $this->makeLinkedStaff('depth-view@example.com', ['login.app']);
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'عرض فقط', '0502000016');

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id]);
        $log = AttendanceLog::query()->where('child_id', $child->id)->firstOrFail();

        $this->actingAs($staff);

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertDontSee(route('nursery.attendance.bulk-check-in'), false)
            ->assertDontSee(route('nursery.attendance.correct', $log), false)
            ->assertDontSee('تصحيح', false);

        $this->post(route('nursery.attendance.bulk-check-in'), [
            'classroom_id' => $room->id,
            'child_ids' => [$child->id],
        ])->assertForbidden();

        $this->post(route('nursery.attendance.bulk-check-out'), [
            'classroom_id' => $room->id,
            'child_ids' => [$child->id],
        ])->assertForbidden();

        $this->patch(route('nursery.attendance.correct', $log), [
            'checked_in_at' => '08:00',
            'status' => AttendanceLog::STATUS_PRESENT,
        ])->assertForbidden();
    }

    #[Test]
    public function attendance_staff_can_bulk_and_correct(): void
    {
        $staff = $this->makeLinkedStaff('depth-att@example.com', ['login.app', 'attendance.children']);
        [$room] = $this->makeTwoClassrooms();
        $a = $this->makeEnrolledChild($room, 'صلاحية أ', '0502000017');
        $b = $this->makeEnrolledChild($room, 'صلاحية ب', '0502000018');

        $this->actingAs($staff);

        $this->post(route('nursery.attendance.bulk-check-in'), [
            'classroom_id' => $room->id,
            'child_ids' => [$a->id, $b->id],
        ])->assertRedirect();

        $log = AttendanceLog::query()->where('child_id', $a->id)->firstOrFail();
        $originalRecorder = $log->recorded_by;

        $this->patch(route('nursery.attendance.correct', $log), [
            'checked_in_at' => '08:07',
            'checked_out_at' => '',
            'status' => AttendanceLog::STATUS_PRESENT,
            'reason' => 'تعديل وقت الباب',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame($originalRecorder, $log->recorded_by);
        $this->assertSame('08:07', $log->checked_in_at?->format('H:i'));
        $this->assertDatabaseHas('nursery_attendance_corrections', [
            'attendance_log_id' => $log->id,
            'corrected_by' => $staff->id,
            'reason' => 'تعديل وقت الباب',
        ]);
    }

    #[Test]
    public function late_uses_shift_start_and_grace_and_keeps_actual_time(): void
    {
        config(['nursery.shift_late_grace_minutes' => 15]);
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'وليد', '0502000019');
        $this->makeShift('08:00', '14:00');

        Carbon::setTestNow(Carbon::parse('2026-08-20 08:20:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'child_id' => $child->id,
            'status' => AttendanceLog::STATUS_LATE,
        ]);
        $this->assertSame('08:20', AttendanceLog::query()->where('child_id', $child->id)->first()?->checked_in_at?->format('H:i'));

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('حاضر 08:20')
            ->assertSee('متأخر');
    }

    #[Test]
    public function on_time_within_grace_is_not_late_and_no_shift_does_not_fake_late(): void
    {
        config(['nursery.shift_late_grace_minutes' => 15]);
        [$room] = $this->makeTwoClassrooms();
        $onTime = $this->makeEnrolledChild($room, 'في الوقت', '0502000020');
        $this->makeShift('08:00', '14:00');

        Carbon::setTestNow(Carbon::parse('2026-08-20 08:10:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $onTime->id])->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'child_id' => $onTime->id,
            'status' => AttendanceLog::STATUS_PRESENT,
        ]);
        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('حاضر 08:10')
            ->assertDontSee('متأخر');

        $noShiftRoom = Classroom::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'بدون مناوبة اختبار',
            'capacity' => 8,
            'age_groups' => ['3_4y'],
            'is_active' => true,
        ]);
        NurseryShift::query()->where('user_id', $this->tenant->id)->update(['is_active' => false]);
        $plain = $this->makeEnrolledChild($noShiftRoom, 'بدون مناوبة', '0502000021');

        Carbon::setTestNow(Carbon::parse('2026-08-20 10:40:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $plain->id])->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'child_id' => $plain->id,
            'status' => AttendanceLog::STATUS_PRESENT,
        ]);
        $this->get(route('nursery.classrooms.today', $noShiftRoom))
            ->assertOk()
            ->assertDontSee('متأخر');
    }

    #[Test]
    public function early_departure_only_when_checkout_is_before_shift_end(): void
    {
        config(['nursery.shift_late_grace_minutes' => 15]);
        [$room] = $this->makeTwoClassrooms();
        $early = $this->makeEnrolledChild($room, 'مبكر', '0502000022');
        $onTime = $this->makeEnrolledChild($room, 'نهاية الدوام', '0502000023');
        $this->makeShift('08:00', '14:00');

        Carbon::setTestNow(Carbon::parse('2026-08-20 08:05:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $early->id]);
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $onTime->id]);

        Carbon::setTestNow(Carbon::parse('2026-08-20 13:20:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-out'), ['child_id' => $early->id]);

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('انصرف 13:20')
            ->assertSee('مغادرة مبكرة');

        Carbon::setTestNow(Carbon::parse('2026-08-20 14:00:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-out'), ['child_id' => $onTime->id]);

        $shifts = app(\App\Services\Nursery\NurseryShiftAttendanceService::class);
        $this->assertTrue($shifts->isChildEarlyDeparture(
            (int) $this->tenant->id,
            AttendanceLog::query()->where('child_id', $early->id)->firstOrFail(),
        ));
        $this->assertFalse($shifts->isChildEarlyDeparture(
            (int) $this->tenant->id,
            AttendanceLog::query()->where('child_id', $onTime->id)->firstOrFail(),
        ));

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('انصرف 14:00')
            ->assertSee('مغادرة مبكرة');
    }

    #[Test]
    public function correction_preserves_original_recorder_and_writes_audit_row(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'تصحيح', '0502000024');
        $staff = $this->makeLinkedStaff('depth-correct@example.com', ['login.app', 'attendance.children']);

        $this->actingAs($staff);
        Carbon::setTestNow(Carbon::parse('2026-08-20 08:12:00', config('app.timezone')));
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id]);
        $this->post(route('nursery.attendance.check-out'), ['child_id' => $child->id]);

        $log = AttendanceLog::query()->where('child_id', $child->id)->firstOrFail();
        $beforeIn = $log->checked_in_at?->format('Y-m-d H:i:s');
        $this->assertSame($staff->id, $log->recorded_by);

        $this->actingAs($this->tenant);
        Carbon::setTestNow(Carbon::parse('2026-08-20 15:00:00', config('app.timezone')));
        $this->patch(route('nursery.attendance.correct', $log), [
            'checked_in_at' => '08:05',
            'checked_out_at' => '13:40',
            'status' => AttendanceLog::STATUS_PRESENT,
            'reason' => 'تصحيح إداري',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame($staff->id, $log->recorded_by);
        $this->assertSame('08:05', $log->checked_in_at?->format('H:i'));
        $this->assertSame('13:40', $log->checked_out_at?->format('H:i'));

        $this->assertDatabaseHas('nursery_attendance_corrections', [
            'user_id' => $this->tenant->id,
            'attendance_log_id' => $log->id,
            'corrected_by' => $this->tenant->id,
            'reason' => 'تصحيح إداري',
        ]);

        $correction = $log->corrections()->first();
        $this->assertNotNull($correction);
        $this->assertSame($beforeIn, $correction->before_state['checked_in_at'] ?? null);
        $this->assertStringContainsString('08:05', (string) ($correction->after_state['checked_in_at'] ?? ''));
    }

    #[Test]
    public function duplicate_check_in_is_rejected_and_unique_constraint_holds(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $child = $this->makeEnrolledChild($room, 'تكرار', '0502000025');

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])->assertRedirect();
        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, AttendanceLog::query()->where('child_id', $child->id)->count());

        try {
            AttendanceLog::query()->create([
                'user_id' => (int) $this->tenant->id,
                'child_id' => $child->id,
                'attendance_date' => now()->toDateString(),
                'checked_in_at' => now(),
                'status' => AttendanceLog::STATUS_PRESENT,
            ]);
            $this->fail('Expected unique constraint on nursery_attendance_logs.');
        } catch (UniqueConstraintViolationException|QueryException) {
            $this->assertSame(1, AttendanceLog::query()->where('child_id', $child->id)->count());
        }
    }

    #[Test]
    public function classroom_today_bulk_ui_uses_eligible_select_and_confirmation(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $waiting = $this->makeEnrolledChild($room, 'بانتظار', '0502000030');
        $present = $this->makeEnrolledChild($room, 'حاضر جماعي', '0502000031');
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $present->id])->assertRedirect();

        $this->get(route('nursery.classrooms.today', $room))
            ->assertOk()
            ->assertSee('تحديد الكل للحضور', false)
            ->assertSee('تحديد الكل للانصراف', false)
            ->assertSee('selectAllCheckIn', false)
            ->assertSee('selectAllCheckOut', false)
            ->assertSee('openBulkConfirm', false)
            ->assertSee('تأكيد الحضور', false)
            ->assertSee('تأكيد الانصراف', false)
            ->assertSee(route('nursery.attendance.bulk-check-in'), false)
            ->assertSee(route('nursery.attendance.check-in'), false)
            ->assertSee('حضور', false)
            ->assertSee('انصراف', false);

        $this->assertDatabaseMissing('nursery_attendance_logs', ['child_id' => $waiting->id]);
    }

    #[Test]
    public function bulk_partial_success_flash_is_clear(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $a = $this->makeEnrolledChild($room, 'جزئي أ', '0502000032');
        $b = $this->makeEnrolledChild($room, 'جزئي ب', '0502000033');

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $a->id])->assertRedirect();

        $this->from(route('nursery.classrooms.today', $room))
            ->post(route('nursery.attendance.bulk-check-in'), [
                'classroom_id' => $room->id,
                'child_ids' => [$a->id, $b->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', function (string $message): bool {
                return str_contains($message, 'تم تسجيل حضور')
                    && str_contains($message, 'وتم تخطي')
                    && str_contains($message, 'لأن حالتهم تغيرت');
            });
    }

    #[Test]
    public function staff_bulk_check_in_and_out_with_skips_and_independent_logs(): void
    {
        $a = $this->makeStaffEmployee('bulk-a@example.com', 'EMP-BA001');
        $b = $this->makeStaffEmployee('bulk-b@example.com', 'EMP-BA002');
        $c = $this->makeStaffEmployee('bulk-c@example.com', 'EMP-BA003');
        $inactive = $this->makeStaffEmployee('bulk-inactive@example.com', 'EMP-BA004', 'inactive');

        $this->post(route('nursery.attendance.staff.check-in'), ['employee_id' => $a->id])->assertRedirect();

        $this->from(route('nursery.attendance.index', ['tab' => 'register']))
            ->post(route('nursery.attendance.staff.bulk-check-in'), [
                'employee_ids' => [$a->id, $b->id, $c->id, $inactive->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('nursery_staff_attendance_logs', [
            'user_id' => $this->tenant->id,
            'employee_id' => $b->id,
        ]);
        $this->assertDatabaseHas('nursery_staff_attendance_logs', [
            'user_id' => $this->tenant->id,
            'employee_id' => $c->id,
        ]);
        $this->assertDatabaseMissing('nursery_staff_attendance_logs', [
            'employee_id' => $inactive->id,
        ]);
        $this->assertSame(3, \App\Models\Nursery\StaffAttendanceLog::query()->where('user_id', $this->tenant->id)->count());

        $this->post(route('nursery.attendance.staff.bulk-check-out'), [
            'employee_ids' => [$a->id, $b->id, $c->id],
        ])->assertRedirect();

        foreach ([$a, $b, $c] as $employee) {
            $this->assertNotNull(
                \App\Models\Nursery\StaffAttendanceLog::query()
                    ->where('employee_id', $employee->id)
                    ->value('checked_out_at')
            );
        }
    }

    #[Test]
    public function staff_bulk_forbidden_without_permission_and_owner_sees_ui(): void
    {
        $target = $this->makeStaffEmployee('bulk-target@example.com', 'EMP-BT001');
        $viewer = $this->makeLinkedStaff('bulk-staff-view@example.com', ['login.app']);

        $this->get(route('nursery.attendance.index', ['tab' => 'register']))
            ->assertOk()
            ->assertSee('تحديد الكل للحضور', false)
            ->assertSee(route('nursery.attendance.staff.bulk-check-in'), false)
            ->assertSee('تأكيد الحضور', false);

        $this->actingAs($viewer);
        $this->post(route('nursery.attendance.staff.bulk-check-in'), [
            'employee_ids' => [$target->id],
        ])->assertForbidden();
    }

    #[Test]
    public function register_tab_child_board_shows_bulk_select_ui_for_owner(): void
    {
        [$room] = $this->makeTwoClassrooms();
        $waiting = $this->makeEnrolledChild($room, 'لوحة انتظار', '0502000090');
        $present = $this->makeEnrolledChild($room, 'لوحة حاضر', '0502000091');
        $this->post(route('nursery.attendance.check-in'), ['child_id' => $present->id])->assertRedirect();

        $this->get(route('nursery.attendance.index', ['tab' => 'register']))
            ->assertOk()
            ->assertSee('تحديد الكل للحضور', false)
            ->assertSee('تحديد الكل للانصراف', false)
            ->assertSee('selectAllCheckIn', false)
            ->assertSee('openBulkConfirm', false)
            ->assertSee(route('nursery.attendance.bulk-check-in'), false)
            ->assertSee(route('nursery.attendance.bulk-check-out'), false)
            ->assertSee($waiting->name, false)
            ->assertSee($present->name, false);

        $viewer = $this->makeLinkedStaff('child-board-view@example.com', ['login.app']);
        $this->actingAs($viewer);
        $this->get(route('nursery.attendance.index', ['tab' => 'register']))
            ->assertOk()
            ->assertDontSee(route('nursery.attendance.bulk-check-in'), false);
    }

    private function makeStaffEmployee(string $email, string $code, string $status = 'active'): Employee
    {
        return Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'code' => $code,
            'name' => 'موظف '.$code,
            'first_name' => 'موظف',
            'last_name' => $code,
            'email' => $email,
            'mobile' => '050'.substr(md5($email), 0, 7),
            'status' => $status,
            'nursery_permissions' => ['login.app'],
        ]);
    }

    /**
     * @return array{0: Classroom, 1: Classroom}
     */
    private function makeTwoClassrooms(): array
    {
        $roomA = Classroom::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'النجوم عمق',
            'capacity' => 12,
            'age_groups' => ['3_4y'],
            'is_active' => true,
        ]);
        $roomB = Classroom::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'البراعم عمق',
            'capacity' => 10,
            'age_groups' => ['2_3y'],
            'is_active' => true,
        ]);

        return [$roomA, $roomB];
    }

    private function makeEnrolledChild(Classroom $room, string $name, string $phone): Child
    {
        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي '.$name,
            'phone' => $phone,
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => $name,
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        Enrollment::query()->create([
            'user_id' => (int) $this->tenant->id,
            'child_id' => $child->id,
            'classroom_id' => $room->id,
            'starts_on' => now()->toDateString(),
            'is_active' => true,
        ]);

        return $child;
    }

    private function makeShift(string $start, string $end): NurseryShift
    {
        return NurseryShift::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'دوام الأطفال',
            'start_time' => $start.':00',
            'end_time' => $end.':00',
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeLinkedStaff(string $email, array $permissions): User
    {
        $user = User::factory()->create([
            'role' => 'supervisor',
            'email' => $email,
            'password' => 'password',
        ]);

        Employee::query()->create([
            'user_id' => (int) $this->tenant->id,
            'linked_user_id' => $user->id,
            'code' => 'EMP-'.substr(md5($email), 0, 6),
            'name' => 'Staff '.$email,
            'email' => $email,
            'status' => 'active',
            'nursery_permissions' => $permissions,
        ]);

        return $user;
    }
}
