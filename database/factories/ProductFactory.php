<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoryCodes = ['DM_DIEN', 'DM_VANPHONG', 'DM_PHUKIEN', 'DM_BAOHO'];
        $units = ['Bộ', 'Cái', 'Chiếc', 'Cuộn', 'Thùng', 'Mét', 'Bịch', 'bịch', 'Gói'];
        $brands = ['Panasonic', 'Sony', 'OP-Master', 'Schneider', 'Rạng Đông'];

        $catCode = fake()->randomElement($categoryCodes);

        return [
            'code' => 'SP'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'category_code' => $catCode,
            'name' => 'Sản phẩm '.fake()->words(3, true),
            'unit' => fake()->randomElement($units),
            'supplier_code' => fake()->randomElement(['NCC001', 'NCC002', 'NCC003', null]),
            'brand' => fake()->randomElement($brands),
            'other_name' => fake()->optional(0.6)->words(2, true),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
