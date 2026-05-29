<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $code = fake()->unique()->numerify('####');

        return [
            'user_id' => User::factory(),
            'code' => $code,
            'name_ar' => 'حساب '.$code,
            'name_en' => 'Account '.$code,
            'type' => Account::TYPE_ASSET,
            'parent_id' => null,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_bank' => false,
            'is_active' => true,
            'allow_direct_posting' => true,
        ];
    }

    public function forTenant(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['type' => $type]);
    }

    public function asset(): static
    {
        return $this->type(Account::TYPE_ASSET);
    }

    public function liability(): static
    {
        return $this->type(Account::TYPE_LIABILITY);
    }

    public function revenue(): static
    {
        return $this->type(Account::TYPE_REVENUE);
    }

    public function expense(): static
    {
        return $this->type(Account::TYPE_EXPENSE);
    }

    public function equity(): static
    {
        return $this->type(Account::TYPE_EQUITY);
    }

    public function withBalances(float $opening, ?float $current = null): static
    {
        return $this->state(fn () => [
            'opening_balance' => $opening,
            'current_balance' => $current ?? $opening,
        ]);
    }
}
