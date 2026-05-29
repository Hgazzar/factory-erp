<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ManufacturingRun;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManufacturingRun>
 */
class ManufacturingRunFactory extends Factory
{
    protected $model = ManufacturingRun::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reference' => 'MR-'.fake()->unique()->numerify('######'),
            'status' => ManufacturingRun::STATUS_POSTED,
            'production_date' => now()->toDateString(),
            'warehouse_id' => Warehouse::factory(),
            'finished_item_id' => Item::factory()->finishedGood(),
            'quantity_produced' => 0,
            'total_materials_cost' => null,
            'notes' => null,
        ];
    }

    public function forTenant(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn () => ['warehouse_id' => $warehouse->id]);
    }

    public function forFinishedItem(Item $item): static
    {
        return $this->state(fn () => ['finished_item_id' => $item->id]);
    }
}
