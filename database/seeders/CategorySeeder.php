<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Faker\Factory as Faker;
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // إنشاء 10 فئات لكل قسم (5 أقسام × 10 = 50 فئة)
        for ($sectionId = 1; $sectionId <= 5; $sectionId++) {
            for ($i = 1; $i <= 10; $i++) {
                $uniqueName = ucfirst($faker->unique()->word) . " " . $sectionId . "-" . $i; // إنشاء اسم فريد

                Category::create([
                    'name' => $uniqueName,
                    'return_able' => $faker->boolean,
                    'publish' => 1,
                    'order' => $i,
                    'section_id' => $sectionId, // ربط الفئة بالقسم
                    'created_by' => 1, // يمكن تغييره بناءً على المستخدم الحالي
                ]);
            }
        }

        // إعادة تعيين الـ Unique Generator لتجنب المشاكل في عمليات Seed لاحقة
        $faker->unique(true);
    }
}
