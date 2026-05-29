<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $code = 'ITM-'.fake()->unique()->numerify('######');

        return [
            'user_id' => User::factory(),
            'code' => $code,
            'name_ar' => 'صنف '.$code,
            'name_en' => 'Item '.$code,
            'unit_id' => Unit::factory(),
            'type' => Item::TYPE_FINISHED_GOOD,
            'current_stock' => 0,
            'min_stock' => 0,
            'cost' => 0,
            'is_active' => true,
        ];
    }

    public function forTenant(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function rawMaterial(): static
    {
        return $this->state(fn () => ['type' => Item::TYPE_RAW_MATERIAL]);
    }

    public function finishedGood(): static
    {
        return $this->state(fn () => ['type' => Item::TYPE_FINISHED_GOOD]);
    }

    public function service(): static
    {
        return $this->state(fn () => ['type' => Item::TYPE_SERVICE]);
    }

    public function withStock(float $qty, float $cost = 0): static
    {
        return $this->state(fn () => [
            'current_stock' => $qty,
            'cost' => $cost,
        ]);
    }
}
