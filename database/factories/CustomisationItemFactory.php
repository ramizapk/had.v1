<?php

namespace Database\Factories;

use App\Models\Customisation;
use App\Models\ProductItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomisationItem>
 */
class CustomisationItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customisation_id' => Customisation::inRandomOrder()->value('id'),
            'product_id' => ProductItem::inRandomOrder()->value('id'), // استخدم product_item_id
            'items' => json_encode([]), // يمكن تعديل البيانات حسب الحاجة
        ];
    }
}
