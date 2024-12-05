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

        for ($i = 1; $i <= 5; $i++) {
            $uniqueName = ucfirst($faker->unique()->word) . " Section " . $i; // اسم مميز لكل قسم

            Section::create([
                'name' => $uniqueName, // اسم فريد
                'image' => 'uploads/sections/section_' . $i . '.jpg', // مسار صورة فريد
                'publish' => 1, // نشر القسم
                'created_by' => $faker->numberBetween(1, 5), // رقم منشئ عشوائي بين 1 و 5
                'updated_by' => null, // فارغ مبدئيًا
            ]);
        }

        // إعادة تعيين الـ Unique Generator لتجنب المشاكل في عمليات Seed لاحقة
        $faker->unique(true);
    }
}
