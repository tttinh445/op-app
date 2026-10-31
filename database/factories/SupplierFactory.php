<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['NPP', 'NCC'];
        $banks = ['Vietcombank', 'Techcombank', 'MB Bank', 'ACB', 'BIDV', 'VPBank'];

        return [
            'code' => 'NCC'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name' => fake()->company(),
            'type' => fake()->randomElement($types),
            'zalo' => '0'.fake()->numberBetween(900000000, 999999999),
            'tax_id' => (string) fake()->unique()->numberBetween(1000000000, 9999999999),
            'bank_account' => (string) fake()->numberBetween(1000000000, 9999999999),
            'bank_name' => fake()->randomElement($banks),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'phone' => '0'.fake()->numberBetween(900000000, 999999999),
            'email' => fake()->companyEmail(),
            'website' => 'https://'.fake()->domainName(),
            'facebook' => 'https://facebook.com/'.fake()->userName(),
            'note' => fake()->sentence(),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
