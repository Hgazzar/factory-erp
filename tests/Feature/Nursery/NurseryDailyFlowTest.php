<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\AttendanceLog;
use App\Models\Nursery\Child;
use App\Models\Nursery\Guardian;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryDailyFlowTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_view_dashboard_register_child_and_check_in(): void
    {
        $this->get(route('nursery.dashboard'))
            ->assertOk()
            ->assertSee('لوحة التحكم')
            ->assertSee('الأطفال النشطون')
            ->assertSee('طاقم العمل')
            ->assertSee('حضور اليوم');

        $guardian = Guardian::query()->create([
            'user_id' => (int) $this->tenant->id,
            'name' => 'ولي الأمر',
            'phone' => '01011112222',
        ]);

        $child = Child::query()->create([
            'user_id' => (int) $this->tenant->id,
            'code' => 'CH-00001',
            'name' => 'آدم',
            'guardian_id' => $guardian->id,
            'status' => Child::STATUS_ACTIVE,
        ]);

        $this->post(route('nursery.attendance.check-in'), ['child_id' => $child->id])
            ->assertRedirect();

        $this->assertDatabaseHas('nursery_attendance_logs', [
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'status' => AttendanceLog::STATUS_PRESENT,
        ]);

        $this->get(route('nursery.children.show', $child))->assertOk()->assertSee('آدم');
    }

    #[Test]
    public function admin_can_register_child_via_form(): void
    {
        $response = $this->post(route('nursery.children.store'), [
            'name' => 'ليان',
            'guardian_name' => 'أم ليان',
            'guardian_phone' => '01099998888',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('nursery_children', [
            'user_id' => $this->tenant->id,
            'name' => 'ليان',
        ]);
    }

    #[Test]
    public function admin_can_open_child_create_and_edit_forms(): void
    {
        $this->get(route('nursery.children.create'))
            ->assertOk()
            ->assertSee('إضافة طفل')
            ->assertSee('المعلومات الأساسية')
            ->assertSee('معلومات ولي الأمر');

        $this->post(route('nursery.children.store'), [
            'name' => 'نور',
            'guardian_name' => 'ولي نور',
            'guardian_phone' => '0501111222',
            'guardian_region' => 'riyadh',
            'guardian_city' => 'الرياض',
        ])->assertRedirect();

        $child = Child::query()->where('name', 'نور')->first();
        $this->assertNotNull($child);

        $this->get(route('nursery.children.edit', $child))
            ->assertOk()
            ->assertSee('تعديل بيانات الطفل')
            ->assertSee('حفظ التعديلات');
    }
}
