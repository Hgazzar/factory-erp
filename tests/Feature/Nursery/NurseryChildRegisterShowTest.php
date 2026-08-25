<?php

declare(strict_types=1);

namespace Tests\Feature\Nursery;

use App\Models\Nursery\Child;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    #[Test]
    public function register_with_avatar_stores_attachment(): void
    {
        Storage::fake('public');
        $avatar = UploadedFile::fake()->image('hamoudi-avatar.png', 160, 160);

        $this->post(route('nursery.children.store'), [
            'name' => 'حمودي',
            'gender' => 'male',
            'date_of_birth' => '2022-05-25',
            'guardian_name' => 'ولي حمودي',
            'guardian_phone' => '0502222333',
            'avatar' => $avatar,
        ])
            ->assertRedirect()
            ->assertSessionMissing('warning')
            ->assertSessionMissing('error');

        $child = Child::query()
            ->where('user_id', $this->tenant->id)
            ->where('name', 'حمودي')
            ->first();
        $this->assertNotNull($child);

        $child->load('attachments');
        $this->assertNotNull($child->avatarAttachment());
        $this->assertNotNull($child->firstImageUrl());
        $this->assertTrue(Storage::disk('public')->exists((string) $child->avatarAttachment()->file_path));
    }
}
