<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Unit;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryUnitTest extends NurseryTestCase
{
    #[Test]
    public function admin_can_create_and_edit_unit_with_goals(): void
    {
        $this->get(route('nursery.units.index'))
            ->assertOk()
            ->assertSee('إجمالي الوحدات');

        $this->get(route('nursery.units.create'))
            ->assertOk()
            ->assertSee('بيانات الوحدة')
            ->assertSee('أهداف الوحدة');

        $this->post(route('nursery.units.store'), [
            'name' => 'الحيوانات',
            'age_groups' => ['2_3y', '3_4y'],
            'goals' => ['التعرف على أصوات الحيوانات', 'تلوين صور الحيوانات'],
        ])->assertRedirect(route('nursery.units.index'));

        $unit = Unit::query()->where('name', 'الحيوانات')->first();
        $this->assertNotNull($unit);
        $this->assertSame(['2_3y', '3_4y'], $unit->age_groups);
        $this->assertSame(
            ['التعرف على أصوات الحيوانات', 'تلوين صور الحيوانات'],
            $unit->goalLines()
        );

        $this->get(route('nursery.units.edit', $unit))
            ->assertOk()
            ->assertSee('حفظ التعديلات');

        $this->put(route('nursery.units.update', $unit), [
            'name' => 'الحيوانات الأليفة',
            'age_groups' => ['4_5y'],
            'goals' => ['رعاية الحيوانات'],
            'is_active' => 'active',
        ])->assertRedirect(route('nursery.units.index', ['tab' => 'active']));

        $unit->refresh();
        $this->assertSame('الحيوانات الأليفة', $unit->name);
        $this->assertSame(['4_5y'], $unit->age_groups);
        $this->assertSame(['رعاية الحيوانات'], $unit->goalLines());
    }
}
