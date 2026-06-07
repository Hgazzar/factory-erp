<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use App\Models\Nursery\ChildMedication;
use App\Models\Nursery\Guardian;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryChildMedicationTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_save_child_medications_on_register(): void
    {
        $this->post(route('nursery.children.store'), [
            'name' => 'ليان',
            'gender' => 'female',
            'guardian_name' => 'أم ليان',
            'guardian_phone' => '0559876543',
            'medications' => [
                [
                    'name' => 'باراسيتامول',
                    'dosage' => '5 مل',
                    'frequency' => ChildMedication::FREQ_TWICE_DAILY,
                    'schedule_notes' => 'بعد الغداء',
                    'notes' => 'عند الحمى فقط',
                ],
            ],
        ])->assertRedirect();

        $child = Child::query()->where('name', 'ليان')->first();
        $this->assertNotNull($child);
        $this->assertDatabaseHas('nursery_child_medications', [
            'user_id' => $this->tenant->id,
            'child_id' => $child->id,
            'name' => 'باراسيتامول',
        ]);

        $this->get(route('nursery.children.show', $child))
            ->assertOk()
            ->assertSee('باراسيتامول')
            ->assertSee('الأدوية');
    }
}
