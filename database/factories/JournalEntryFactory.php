<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'created_by' => fn (array $attrs) => $attrs['user_id'],
            'reference' => 'JE-'.fake()->unique()->numerify('######'),
            'date' => now()->toDateString(),
            'description' => fake()->sentence(),
            'total' => 0,
        ];
    }

    public function forTenant(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'created_by' => $user->id,
        ]);
    }

    public function total(float $amount): static
    {
        return $this->state(fn () => ['total' => $amount]);
    }
}
