<?php

namespace App\Http\Controllers\Api\Customers;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    use ApiResponse;

    public function handleAddress(Request $request, $addressId = null)
    {
        $customer = Auth::user();

        // التحقق من صحة البيانات المدخلة فقط إذا كانت العملية هي إضافة (POST)، تعديل (PUT) أو تعيين الافتراضي (PATCH)
        if ($request->isMethod('post') || $request->isMethod('put')) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'location' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }
        }

        // إذا كانت العملية هي إضافة عنوان جديد
        if ($request->isMethod('post')) {
            // التحقق من عدد العناوين الحالية للعميل
            if ($customer->addresses()->count() >= 20) {
                return $this->errorResponse('لا يمكن إضافة أكثر من 20 عنوانًا للعميل', 400);
            }

            // إنشاء العنوان الجديد
            $address = $customer->addresses()->create([
                'name' => $request->name,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location' => $request->location,
                'is_default' => true, // آخر عنوان سيكون هو الافتراضي
            ]);

            // جعل العناوين الأخرى ليست افتراضية
            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);

            // إرجاع العناوين بتنسيق جديد
            return $this->successResponse($this->formatAddresses($customer), 'تم إضافة العنوان بنجاح', 201);
        }

        // إذا كانت العملية هي عرض جميع العناوين الخاصة بالعميل
        if ($request->isMethod('get')) {
            return $this->successResponse($this->formatAddresses($customer), 'تم جلب العناوين بنجاح');
        }

        // إذا كانت العملية هي تعديل عنوان
        if ($request->isMethod('put') && $addressId) {
            // البحث عن العنوان
            $address = $customer->addresses()->find($addressId);

            if (!$address) {
                return $this->errorResponse('العنوان غير موجود', 404);
            }

            // تحديث العنوان
            $address->update([
                'name' => $request->name,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'location' => $request->location,
            ]);

            // إرجاع العناوين بتنسيق جديد
            return $this->successResponse($this->formatAddresses($customer), 'تم تعديل العنوان بنجاح');
        }

        // إذا كانت العملية هي حذف عنوان
        if ($request->isMethod('delete') && $addressId) {
            // البحث عن العنوان
            $address = $customer->addresses()->find($addressId);

            if (!$address) {
                return $this->errorResponse('العنوان غير موجود', 404);
            }

            // حذف العنوان
            $address->delete();

            // إرجاع العناوين بتنسيق جديد
            return $this->successResponse($this->formatAddresses($customer), 'تم حذف العنوان بنجاح');
        }

        // إذا كانت العملية هي تعيين العنوان الافتراضي
        if ($request->isMethod('patch') && $addressId) {
            // البحث عن العنوان
            $address = $customer->addresses()->find($addressId);

            if (!$address) {
                return $this->errorResponse('العنوان غير موجود', 404);
            }

            // جعل العنوان الافتراضي
            $customer->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);

            // إرجاع العناوين بتنسيق جديد
            return $this->successResponse($this->formatAddresses($customer), 'تم تعيين العنوان كافتراضي');
        }

        return $this->errorResponse('العملية غير مدعومة', 405);
    }

    // دالة لتنسيق العناوين بالشكل المطلوب
    private function formatAddresses($customer)
    {
        $addresses = $customer->addresses;
        $activeAddress = $addresses->where('is_default', true)->first();
        $otherAddresses = $addresses->where('is_default', false);

        return [
            'locations' => [
                'active' => $activeAddress ? [
                    [
                        'id' => $activeAddress->id,
                        'name' => $activeAddress->name,
                        'location' => "{$activeAddress->latitude},{$activeAddress->longitude}"
                    ]
                ] : [],
                'other_locations' => $otherAddresses->map(function ($address) {
                    return [
                        'id' => $address->id,
                        'name' => $address->name,
                        'location' => "{$address->latitude},{$address->longitude}"
                    ];
                })
            ]
        ];
    }
}
