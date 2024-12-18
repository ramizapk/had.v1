<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'name',
        'is_default',
        'location',
        'latitude',
        'longitude',
        'customer_id',
    ];


    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function getAddressAttribute(): array
    {
        return [
            "lat" => (float) $this->latitude,
            "lng" => (float) $this->longitude,
        ];
    }

    public static function getLatLngAttributes(): array
    {
        return [
            'lat' => 'latitude',
            'lng' => 'longitude',
        ];
    }
    public static function getComputedLocation(): string
    {
        return 'location';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }


    public function calculateDeliveryDistanceAndTime($vendor = null, $vendorLat = null, $vendorLong = null)
    {
        // إحداثيات العميل
        $customerLat = $this->latitude;
        $customerLng = $this->longitude;

        // إذا كان هناك فيندور (Vendor) ممرر، نستخدم إحداثياته
        if ($vendor) {
            $vendorLat = $vendor->latitude;
            $vendorLong = $vendor->longitude;
        }

        // إذا كانت إحداثيات الفيندور غير موجودة، نرجع خطأ أو رسالة توضح ذلك
        if (!$vendorLat || !$vendorLong) {
            return [
                'error' => 'Vendor location is required'
            ];
        }

        // حساب المسافة باستخدام صيغة Haversine
        $earthRadius = 6371; // نصف قطر الأرض بالكيلومتر

        $latFrom = deg2rad($customerLat);
        $lonFrom = deg2rad($customerLng);
        $latTo = deg2rad($vendorLat);
        $lonTo = deg2rad($vendorLong);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($latFrom) * cos($latTo) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = $earthRadius * $c; // المسافة بالكيلومتر

        // تحويل المسافة إلى متر
        $distanceM = $distanceKm * 1000;

        // حساب الوقت التقريبي للتوصيل (افتراض أن المتوسط هو 40 كم/ساعة)
        $deliveryTimeHours = $distanceKm / 40; // الزمن بالساعة
        $deliveryTimeMinutes = round($deliveryTimeHours * 60); // الزمن بالدقائق

        return [
            'distance_km' => round($distanceKm, 2),
            'distance_m' => round($distanceM, 0),
            'estimated_time_minutes' => $deliveryTimeMinutes,
        ];
    }

}
