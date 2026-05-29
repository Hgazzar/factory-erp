<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalItem>
 */
class JournalItemFactory extends Factory
{
    protected $model = JournalItem::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'description' => fake()->sentence(),
            'debit' => 0,
            'credit' => 0,
        ];
    }

    public function forTenant(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function debit(float $amount): static
    {
        return $this->state(fn () => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    public function credit(float $amount): static
    {
        return $this->state(fn () => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
