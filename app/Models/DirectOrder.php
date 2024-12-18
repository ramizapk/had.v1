<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class DirectOrder extends Model
{
    use HasFactory;

    // الحقول القابلة للتعبئة
    protected $fillable = [
        'customer_lat',
        'customer_long',
        'vendor_lat',
        'vendor_long',
        'distance',
        'delivery_fee',
        'status',
        'payment_method',
        'payment_status',
        'customer_id',
        'delivery_agent_id',
        'is_vendor',
        'vendor_id',
        'vendor_name',
    ];

    /**
     * العلاقة مع عناصر الطلب المباشر.
     */
    public function items()
    {
        return $this->hasMany(DirectOrderItem::class);
    }

    /**
     * العلاقة مع الكاستمر.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * العلاقة مع السائق (Delivery Agent).
     */
    public function deliveryAgent()
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id');
    }

    /**
     * العلاقة مع الفيندور (Vendor).
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
