<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApplicationSetting;
use App\Traits\ApiResponse;
class ApplicationSettingController extends Controller
{
    use ApiResponse;

    /**
     * دالة لإضافة أو تعديل الإعدادات
     */
    public function upsertSettings(Request $request)
    {
        // التحقق من صحة البيانات
        $request->validate([
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i',
            'whatsapp_number' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'facebook_page' => 'nullable|url',
            'instagram_page' => 'nullable|url',
            'twitter_page' => 'nullable|url',
            'tiktok_page' => 'nullable|url',
            'is_open' => 'nullable|boolean',
            'closure_reason' => 'nullable|string|max:500',
            'delivery_price_first_3km' => 'nullable|numeric|min:0',
            'delivery_price_additional_per_km' => 'nullable|numeric|min:0',
        ]);

        // الحصول على السجل الحالي (إذا كان موجودًا)
        $settings = ApplicationSetting::first();

        // تحديد الحقول المراد تحديثها
        $data = $request->only([
            'open_time',
            'close_time',
            'whatsapp_number',
            'contact_number',
            'facebook_page',
            'instagram_page',
            'twitter_page',
            'tiktok_page',
            'is_open',
            'closure_reason',
            'delivery_price_first_3km',
            'delivery_price_additional_per_km',
        ]);

        if ($settings) {
            // تحديث الإعدادات الحالية
            $settings->update($data);
            return $this->successResponse($settings, 'Application settings updated successfully.');
        } else {
            // إنشاء سجل جديد (في حالة عدم وجود سجل مسبق)
            $settings = ApplicationSetting::create($data);
            return $this->successResponse($settings, 'Application settings created successfully.');
        }
    }


    /**
     * دالة لعرض الإعدادات
     */
    public function getSettings()
    {
        $settings = ApplicationSetting::first();

        if (!$settings) {
            return $this->errorResponse('No application settings found.', 404);
        }

        return $this->successResponse($settings, 'Application settings retrieved successfully.');
    }
}
