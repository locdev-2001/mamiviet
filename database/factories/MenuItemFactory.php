<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\MenuCategory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoryId = MenuCategory::inRandomOrder()->first()?->id ?? 1;
        $ingredientsList = [
            ['beef', 'potato', 'onion'],
            ['chicken', 'rice', 'pepper'],
            ['salmon', 'lemon', 'dill'],
            ['pasta', 'tomato', 'basil'],
            ['cheese', 'bread', 'ham'],
            ['egg', 'bacon', 'cheese'],
            ['shrimp', 'garlic', 'butter'],
            ['duck', 'orange', 'spice'],
            ['apple', 'cinnamon', 'sugar'],
            ['chocolate', 'cream', 'strawberry'],
        ];
        $idx = $this->faker->numberBetween(0, 9);
        $price = $this->faker->randomFloat(2, 5, 40); // Euro
        $hasDiscount = $this->faker->boolean(40);
        $discount = $hasDiscount ? $this->faker->randomFloat(2, 3, $price - 1) : null;
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'menu_category_id' => $categoryId,
            'price' => $price,
            'discount_price' => $discount,
            'available' => true,
            'is_featured' => $this->faker->boolean(30),
            'order_count' => $this->faker->numberBetween(0, 100),
            'ingredients' => $ingredientsList[$idx],
        ];
    }
}
