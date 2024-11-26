<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 20; $i++) {
            Section::create([
                'name' => $faker->word, // توليد اسم عشوائي
                'image' => $faker->unique()->word . '.jpg', // صورة عشوائية
                'publish' => $faker->boolean, // نشر أو عدم نشر (true/false)
                'created_by' => $faker->numberBetween(1, 5), // رقم منشئ عشوائي بين 1 و 5
                'updated_by' => null,
            ]);
        }
    }
}
