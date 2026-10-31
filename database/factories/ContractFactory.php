<?php

namespace Database\Factories;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'HD-'.strtoupper(fake()->unique()->bothify('??###')),
            'contract_number' => fake()->optional()->bothify('HD-###/2026'),
            'contract_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'partner' => 'CUST-'.fake()->numerify('####'),
            'amount' => fake()->randomFloat(2, 1000000, 100000000),
            'status' => fake()->randomElement(Contract::STATUSES),
            'type' => fake()->randomElement(Contract::TYPES),
            'email' => fake()->optional()->safeEmail(),
            'link' => fake()->optional()->url(),
            'quote_link' => fake()->optional()->url(),
            'note' => fake()->optional()->sentence(),
            'salesperson' => fake()->optional()->name(),
            'sale_discount' => fake()->optional()->randomFloat(2, 100000, 5000000),
            'sale_payment_status' => fake()->randomElement(Contract::SALE_PAYMENT_STATUSES),
        ];
    }
}
