<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\App\CustomerResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ProfileController extends Controller
{
    use ApiResponse;
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $customer = auth()->user(); // الحصول على المستخدم الحالي

        // التحقق من وجود صورة قديمة وحذفها إذا كانت موجودة
        if ($customer->avatar && Storage::exists($customer->avatar)) {
            Storage::delete($customer->avatar); // حذف الصورة القديمة
        }

        // تحديد المسار الخاص بتحميل الصورة
        $avatarPath = $request->file('avatar')->store($this->customerAvatarsPath); // استخدام المسار من الـ Controller

        $customer->avatar = $avatarPath; // حفظ المسار في قاعدة البيانات
        $customer->save();
        $customer->load('addresses');
        return $this->successResponse(new CustomerResource($customer), 'تم تحديث الصورة بنجاح');
    }


    /**
     * جلب معلومات المستخدم
     */
    public function getUserProfile()
    {
        $customer = auth()->user(); // الحصول على بيانات المستخدم الحالي
        $customer->load('addresses');
        return $this->successResponse(new CustomerResource($customer), 'تم جلب بيانات المستخدم بنجاح');
    }
}
