<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $code = 'U-'.fake()->unique()->numerify('####');

        return [
            'code' => $code,
            'name_ar' => 'وحدة '.$code,
            'name_en' => 'Unit '.$code,
            'symbol' => 'قطعة',
            'conversion_factor' => 1,
            'is_active' => true,
        ];
    }
}
