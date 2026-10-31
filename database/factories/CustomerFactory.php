<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['Công ty', 'Khách vãng lai'];
        $banks = ['Vietcombank', 'Techcombank', 'MB Bank', 'ACB', 'BIDV', 'VPBank'];

        return [
            'code' => 'KH'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => fake()->company(),
            'type' => fake()->randomElement($types),
            'tax_id' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'phone' => '0'.fake()->numberBetween(900000000, 999999999),
            'email' => fake()->safeEmail(),
            'bank_account' => (string) fake()->numberBetween(1000000000, 9999999999),
            'bank_name' => fake()->randomElement($banks),
            'note' => fake()->sentence(),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
