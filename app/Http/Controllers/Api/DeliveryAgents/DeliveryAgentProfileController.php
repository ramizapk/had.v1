<?php

namespace App\Http\Controllers\Api\DeliveryAgents;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryAgent\DeliveryAgentResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class DeliveryAgentProfileController extends Controller
{
    use ApiResponse;

    /**
     * تحديث الموقع (الخط الطول والعرض)
     */
    public function updateLocation(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // الحصول على العامل
        $agent = $request->user();

        // تحديث الموقع
        $agent->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // إعادة البيانات بعد التحديث
        return $this->successResponse(new DeliveryAgentResource($agent), 'تم تحديث الموقع بنجاح');
    }

    public function uploadAvatar(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,bmp,tiff|max:2048',  // الحجم الأقصى 2 ميغابايت
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // الحصول على العامل
        $agent = $request->user();

        // إذا كانت الصورة مرفوعة، نحفظها
        if ($request->hasFile('avatar')) {
            // إذا كانت هناك صورة قديمة، نحذفها أولًا
            if ($agent->avatar && file_exists(public_path('storage/' . $agent->avatar))) {
                unlink(public_path('storage/' . $agent->avatar)); // حذف الصورة القديمة
            }

            // حفظ الصورة على المسار المحلي (public)
            $avatarPath = $request->file('avatar')->store('avatars', 'public');

            // تحديث حقل avatar في قاعدة البيانات
            $agent->update([
                'avatar' => $avatarPath, // حفظ المسار الجديد
            ]);
        }

        // إعادة البيانات بعد التحديث
        return $this->successResponse(new DeliveryAgentResource($agent), 'تم رفع الصورة بنجاح');
    }
}
