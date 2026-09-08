<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $costPerUnit = $this->faker->randomFloat(2, 5, 100);
        $sellingPrice = $costPerUnit * $this->faker->randomFloat(2, 1.2, 3.0);

        return [
            'name' => $this->faker->word() . ' ' . $this->faker->word(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####-??')),
            'unit' => 'pc',
            'cost_per_unit' => $costPerUnit,
            'selling_price' => round($sellingPrice, 2),
            'min_stock' => rand(5, 20),
        ];
    }
}
