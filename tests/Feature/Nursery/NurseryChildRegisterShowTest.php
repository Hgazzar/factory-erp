<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\NurseryTestCase;

final class NurseryChildRegisterShowTest extends NurseryTestCase
{
    #[Test]
    public function register_then_show_and_index_succeed(): void
    {
        $response = $this->post(route('nursery.children.store'), [
            'name' => 'حمد',
            'gender' => 'male',
            'date_of_birth' => '2022-01-25',
            'guardian_name' => 'ولي حمد',
            'guardian_phone' => '0501111222',
            'guardian_relationship' => 'father',
        ]);

        $response->assertRedirect();
        $child = Child::query()->where('user_id', $this->tenant->id)->where('name', 'حمد')->first();
        $this->assertNotNull($child);

        $this->get(route('nursery.children.show', $child))->assertOk()->assertSee('حمد');
        $this->get(route('nursery.children.index'))->assertOk()->assertSee('حمد');

        // second save opens the already-registered child instead of a confusing error
        $this->from(route('nursery.children.create'))
            ->post(route('nursery.children.store'), [
                'name' => 'حمد',
                'gender' => 'male',
                'date_of_birth' => '2022-01-25',
                'guardian_name' => 'ولي حمد',
                'guardian_phone' => '0501111222',
            ])
            ->assertRedirect(route('nursery.children.show', $child))
            ->assertSessionHas('success');
    }
}
