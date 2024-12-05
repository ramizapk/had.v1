<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // إنشاء نوعي الخدمات
        $serviceTypes = [
            ['name' => 'خدمات حكومية', 'description' => 'خدمات حكومية متنوعة', 'image' => 'uploads/service_types/government.png'],
            ['name' => 'خدمات النقل والتوصيل', 'description' => 'خدمات النقل والتوصيل السريع', 'image' => 'uploads/service_types/delivery.png'],
        ];

        foreach ($serviceTypes as $type) {
            ServiceType::create($type);
        }
    }
}
