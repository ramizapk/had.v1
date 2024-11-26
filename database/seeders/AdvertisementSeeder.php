<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Advertisement;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Section;
use App\Models\User;
use Faker\Factory as Faker;
class AdvertisementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();


        foreach (range(1, 10) as $index) {
            $type = $faker->randomElement(['internal', 'external']);
            $placement = $faker->randomElement(['main_page', 'specific_section']);


            if ($type == 'internal') {
                $targetLink = null;
                $vendorId = $faker->boolean ? Vendor::inRandomOrder()->first()->id : null;
                $productId = $faker->boolean ? Product::inRandomOrder()->first()->id : null;
            } else {
                $targetLink = $faker->url;
                $vendorId = null;
                $productId = null;
            }


            if ($placement == 'specific_section') {
                $sectionId = Section::inRandomOrder()->first()->id;
            } else {
                $sectionId = null;
            }


            if ($vendorId !== null) {
                $productId = null;
            }

            Advertisement::create([
                'name' => $faker->company,
                'description' => $faker->paragraph,
                'image' => $faker->imageUrl(800, 600, 'business', true, 'Faker'),
                'type' => $type,
                'price' => $faker->randomFloat(2, 10, 500),
                'status' => $faker->randomElement(['pending', 'active', 'expired']),
                'start_date' => $faker->date,
                'end_date' => $faker->date,
                'placement' => $placement,
                'section_id' => $sectionId,
                'vendor_id' => $vendorId,
                'product_id' => $productId,
                'target_link' => $targetLink,
                'created_by' => User::inRandomOrder()->first()->id,
                'updated_by' => User::inRandomOrder()->first()->id,
            ]);
        }
    }
}
