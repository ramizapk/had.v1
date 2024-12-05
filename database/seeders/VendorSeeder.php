<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\Section;
use App\Models\User;
use Faker\Factory as Faker;
use App\Models\WorkTime;
class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // جلب IDs من الجداول المرتبطة
        $sections = Section::all();
        $userIds = User::pluck('id')->toArray();

        // أيام الأسبوع
        $daysOfWeek = [
            'Saturday',
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday'
        ];

        // لكل قسم، أنشئ 15 مزود
        foreach ($sections as $section) {
            for ($i = 1; $i <= 15; $i++) {
                // إنشاء Vendor
                $vendor = Vendor::create([
                    'name' => $faker->company,
                    'phone_one' => $faker->phoneNumber,
                    'phone_two' => $faker->optional()->phoneNumber, // رقم هاتف ثانٍ اختياري
                    'email' => $faker->unique()->companyEmail,
                    'icon' => 'uploads/vendors/' . $faker->uuid . '.jpg', // مسار أيقونة افتراضي
                    'publish' => 1, // منشور أو غير منشور
                    'address' => $faker->address,
                    'latitude' => $faker->latitude,
                    'longitude' => $faker->longitude,
                    'section_id' => $section->id, // ربط المزود بالقسم الحالي
                    'created_by' => $faker->randomElement($userIds), // منشئ عشوائي
                    'updated_by' => $faker->optional()->randomElement($userIds), // محدث عشوائي
                ]);

                // إنشاء أوقات العمل لكل يوم من أيام الأسبوع
                foreach ($daysOfWeek as $day) {
                    WorkTime::create([
                        'day_name' => $day,
                        'from' => $faker->time('H:i', '09:00'), // وقت بداية عشوائي
                        'to' => $faker->time('H:i', '17:00'), // وقت نهاية عشوائي
                        'is_open' => $faker->boolean(90), // مفتوح 90% من الوقت
                        'vendor_id' => $vendor->id, // ربط أوقات العمل بالمزود
                        'created_by' => $vendor->created_by, // نفس منشئ البائع
                        'updated_by' => $vendor->updated_by, // نفس محدث البائع
                    ]);
                }
            }
        }
    }
}
