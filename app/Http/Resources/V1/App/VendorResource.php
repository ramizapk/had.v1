<?php
namespace App\Http\Resources\V1\App;

use App\Http\Resources\V1\Admin\WorkTimeResource;
use App\Models\Address;
use App\Models\VendorFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isFavorite = false;
        $deliveryDetails = null;

        // التحقق مما إذا كان المستخدم مسجل دخول وأضفى البائع للمفضلة
        if (auth('sanctum')->check()) {
            $isFavorite = VendorFavorite::where('customer_id', auth('sanctum')->id())
                ->where('vendor_id', $this->id)
                ->exists();

            // جلب العنوان الافتراضي للمستخدم لحساب تفاصيل التوصيل
            $defaultAddress = Address::where('customer_id', auth('sanctum')->id())->where('is_default', true)->first();

            if ($defaultAddress) {
                // حساب تفاصيل التوصيل بناءً على العنوان الافتراضي
                $deliveryDetails = $defaultAddress->calculateDeliveryDistanceAndTime($this->resource); // استخدم $this->resource هنا للإشارة إلى كائن Vendor
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone_one' => $this->phone_one,
            'phone_two' => $this->phone_two,
            'email' => $this->email,
            'icon' => $this->icon ? Storage::url($this->icon) : null,  // عرض الرابط الكامل للصورة
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'section' => $this->section ? $this->section->name : null,
            'work_times' => WorkTimeResource::collection($this->workTimes),
            'is_favorite' => $isFavorite,
            'direct_order' => $this->direct_order,
            'is_service_provider' => $this->is_service_provider,
            'delivery_details' => $deliveryDetails ? [
                'distance' => $deliveryDetails['distance_km'],
                'estimated_time' => $deliveryDetails['estimated_time_minutes'],
                'delivery_fee' => $this->calculateDeliveryFee($deliveryDetails['distance_km']),
            ] : null,
        ];
    }

    /**
     * حساب رسوم التوصيل بناءً على المسافة.
     */
    private function calculateDeliveryFee($distanceKm)
    {
        $baseFee = 500; // سعر أساسي للتوصيل
        $perKmFee = 100; // تكلفة إضافية لكل كيلو متر بعد الـ 3 كيلومترات
        $freeDistance = 3; // المسافة المجانية (3 كيلومترات)

        if ($distanceKm <= $freeDistance) {
            return $baseFee;
        }

        $extraDistance = $distanceKm - $freeDistance;
        return $baseFee + ($extraDistance * $perKmFee);
    }
}
