<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $code = 'WH-'.fake()->unique()->numerify('####');

        return [
            'user_id' => User::factory(),
            'code' => $code,
            'name_ar' => 'مستودع '.$code,
            'name_en' => 'Warehouse '.$code,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function forTenant(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
