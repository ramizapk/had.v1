<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\ServiceType;
class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $services = [
            // خدمات حكومية
            ['name' => 'تجديد بطاقة الأحوال', 'description' => 'تجديد بطاقة الأحوال المدنية بسهولة', 'image' => 'uploads/services/id_renewal.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'استخراج شهادة ميلاد', 'description' => 'استخراج شهادة ميلاد رسمية', 'image' => 'uploads/services/birth_certificate.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'تحديث بيانات السجل المدني', 'description' => 'تحديث بيانات السجل المدني عبر الإنترنت', 'image' => 'uploads/services/civil_registry_update.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'استخراج جواز سفر', 'description' => 'استخراج جواز السفر بسهولة', 'image' => 'uploads/services/passport.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'تصديق الوثائق الرسمية', 'description' => 'تصديق الوثائق من الجهات المختصة', 'image' => 'uploads/services/document_auth.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'تقديم طلب إقامة', 'description' => 'تقديم طلب إقامة جديد', 'image' => 'uploads/services/residency_request.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'إصدار شهادة وفاة', 'description' => 'إصدار شهادة وفاة رسمية', 'image' => 'uploads/services/death_certificate.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'فتح سجل تجاري', 'description' => 'فتح سجل تجاري للمشاريع', 'image' => 'uploads/services/business_register.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'إصدار رخصة قيادة', 'description' => 'إصدار رخصة قيادة جديدة', 'image' => 'uploads/services/driving_license.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'إصدار بطاقة ضريبية', 'description' => 'إصدار بطاقة ضريبية للشركات', 'image' => 'uploads/services/tax_card.png', 'service_type_id' => 1, 'whatsapp_link' => 'https://wa.me/123456789'],

            // خدمات النقل والتوصيل
            ['name' => 'توصيل الطرود', 'description' => 'توصيل الطرود بشكل سريع وآمن', 'image' => 'uploads/services/parcel_delivery.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'توصيل الأفراد', 'description' => 'خدمة نقل الأفراد بجودة عالية', 'image' => 'uploads/services/people_transport.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'توصيل الطعام', 'description' => 'توصيل الطلبات من المطاعم', 'image' => 'uploads/services/food_delivery.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'توصيل المستندات', 'description' => 'خدمة توصيل المستندات الهامة', 'image' => 'uploads/services/document_delivery.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'توصيل الأدوية', 'description' => 'توصيل الأدوية والمستلزمات الطبية', 'image' => 'uploads/services/medicine_delivery.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'خدمات نقل الأثاث', 'description' => 'نقل الأثاث بأمان وسرعة', 'image' => 'uploads/services/furniture_transport.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'توصيل الهدايا', 'description' => 'توصيل الهدايا لمناسباتك الخاصة', 'image' => 'uploads/services/gift_delivery.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'خدمات البريد السريع', 'description' => 'توصيل البريد والمستندات بسرعة', 'image' => 'uploads/services/courier_service.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'خدمات النقل الجماعي', 'description' => 'توفير حافلات للنقل الجماعي', 'image' => 'uploads/services/group_transport.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
            ['name' => 'خدمة الشحن الدولي', 'description' => 'شحن دولي سريع وآمن', 'image' => 'uploads/services/international_shipping.png', 'service_type_id' => 2, 'whatsapp_link' => 'https://wa.me/123456789'],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
