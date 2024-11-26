<?php

namespace Database\Factories;

use App\Models\Customisation;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomProduct>
 */
class CustomProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'note' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 1, 50),
            'vendor_id' => Vendor::inRandomOrder()->value('id'),
            'customisation_id' => Customisation::inRandomOrder()->value('id'),
        ];
    }
}
