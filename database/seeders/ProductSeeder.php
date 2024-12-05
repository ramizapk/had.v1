<?php

namespace Database\Seeders;

use App\Models\CustomisationItem;
use Illuminate\Database\Seeder;
use App\Models\CategoryVendor;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\ProductImage;
use App\Models\Customisation;
use App\Models\CustomProduct;
use Illuminate\Support\Str;
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // الحصول على جميع روابط الفئات والمزودين
        $categoryVendors = CategoryVendor::all();

        foreach ($categoryVendors as $categoryVendor) {
            $vendorId = $categoryVendor->vendor_id;
            $categoryId = $categoryVendor->category_id;

            // إنشاء 5 منتجات لكل فئة مرتبطة بمزود
            for ($i = 1; $i <= 5; $i++) {
                $product = Product::create([
                    'product_name' => "Product {$i} for Vendor {$vendorId} in Category {$categoryId}",
                    'description' => "Description for Product {$i} in Category {$categoryId}",
                    'publish' => rand(0, 1),
                    'vendor_id' => $vendorId,
                    'category_id' => $categoryId,
                    'created_by' => 1, // أو معرف المستخدم الإداري
                ]);

                // إضافة صور للمنتج
                for ($j = 1; $j <= rand(1, 3); $j++) {
                    ProductImage::create([
                        'img_url' => 'uploads/products/' . Str::random(10) . '.jpg',
                        'is_default' => $j === 1 ? true : false,
                        'product_id' => $product->id,
                    ]);
                }

                // إضافة عناصر (Product Items) للمنتج
                for ($k = 1; $k <= rand(1, 5); $k++) {
                    $productItem = ProductItem::create([
                        'name' => "Item {$k} for Product {$product->id}",
                        'description' => "Description for Item {$k}",
                        'price' => rand(100, 1000) / 10,
                        'publish' => rand(0, 1),
                        'product_id' => $product->id,
                        'created_by' => 1,
                    ]);

                    // إضافة تخصيصات (Customisations) للعنصر
                    for ($l = 1; $l <= rand(1, 3); $l++) {
                        $customisation = Customisation::create([
                            'name' => "Customisation {$l} for Item {$k}",
                            'note' => "Note for Customisation {$l}",
                            'vendor_id' => $vendorId,
                            'is_multi_select' => rand(0, 1),
                        ]);

                        // إضافة منتجات مخصصة (Custom Products) داخل التخصيص
                        $customProducts = [];
                        for ($m = 1; $m <= rand(2, 5); $m++) {
                            $customProduct = CustomProduct::create([
                                'name' => "Custom Product {$m} for Customisation {$l}",
                                'note' => "Note for Custom Product {$m}",
                                'price' => rand(50, 500) / 10,
                                'vendor_id' => $vendorId,
                                'customisation_id' => $customisation->id,
                            ]);

                            $customProducts[] = [
                                'id' => $customProduct->id,
                                'name' => $customProduct->name,
                                'price' => $customProduct->price,
                            ];
                        }

                        // ربط التخصيص بالعنصر
                        CustomisationItem::create([
                            'customisation_id' => $customisation->id,
                            'product_id' => $productItem->id,
                            'items' => json_encode($customProducts),
                        ]);
                    }
                }
            }
        }
    }
}