<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'code' => 'ORD-'.strtoupper(fake()->unique()->bothify('??###')),
            'name' => fake()->optional()->sentence(3),
            'customer' => 'CUST-'.fake()->numerify('####'),
            'type' => fake()->randomElement(Order::TYPES),
            'total_amount' => fake()->randomFloat(2, 500000, 50000000),
            'status' => fake()->randomElement(Order::STATUSES),
            'quote_url' => fake()->optional()->url(),
            'note' => fake()->optional()->sentence(),
            'salesperson' => fake()->optional()->name(),
            'sale_discount' => fake()->optional()->randomFloat(2, 50000, 2000000),
            'sale_payment_status' => fake()->randomElement(Order::SALE_PAYMENT_STATUSES),
        ];
    }
}
