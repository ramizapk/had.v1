<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\CategoryVendor;
class CategoryVendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // توزيع الفئات على المزودين
        Vendor::all()->each(function ($vendor) {
            // الحصول على الفئات التي تتبع القسم المرتبط بالمزود
            $categories = Category::where('section_id', $vendor->section_id)->pluck('id')->toArray();

            // اختيار 5 فئات عشوائية من نفس القسم
            $randomCategories = collect($categories)->random(5);

            foreach ($randomCategories as $categoryId) {
                CategoryVendor::create([
                    'vendor_id' => $vendor->id,
                    'category_id' => $categoryId,
                    'show_in_menu' => 1, // أو حسب الحاجة
                ]);
            }
        });
    }
}
