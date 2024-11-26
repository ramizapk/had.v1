<?php

namespace Database\Seeders;

use App\Models\CustomisationItem;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductImage;
use App\Models\Customisation;
use App\Models\CustomProduct;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory(5) // إنشاء 5 منتجات
            ->has(
                ProductImage::factory(3), // 3 صور لكل منتج
                'images'
            )
            ->has(
                ProductItem::factory(3) // 3 عناصر لكل منتج
                    ->has(
                        CustomisationItem::factory(2) // ربط التخصيصات عبر الجدول الوسيط
                            ->for(Customisation::factory()->has(CustomProduct::factory(3), 'customProducts'), 'customisation'),
                        'customisations'
                    ),
                'items'
            )
            ->create();
    }
}