<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\Section;
use App\Models\User;
use Faker\Factory as Faker;
class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // جلب IDs من الجداول المرتبطة
        $sectionIds = Section::pluck('id')->toArray();
        $userIds = User::pluck('id')->toArray();

        for ($i = 1; $i <= 50; $i++) {
            Vendor::create([
                'name' => $faker->company,
                'phone_one' => $faker->phoneNumber,
                'phone_two' => $faker->optional()->phoneNumber, // رقم هاتف ثانٍ اختياري
                'email' => $faker->unique()->companyEmail,
                'icon' => $faker->unique()->word . '.jpg', // مسار أيقونة افتراضي
                'publish' => $faker->boolean, // منشور أو غير منشور
                'address' => $faker->address,
                'latitude' => $faker->latitude,
                'longitude' => $faker->longitude,
                'section_id' => $faker->randomElement($sectionIds), // قسم عشوائي
                'created_by' => $faker->randomElement($userIds), // منشئ عشوائي
                'updated_by' => $faker->optional()->randomElement($userIds), // محدث عشوائي
            ]);
        }
    }
}
