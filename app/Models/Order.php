<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vendor_id',
        'customer_location',
        'customer_latitude',
        'customer_longitude',
        'delivery_agent_id',
        'status',
        'total_price',
        'delivery_fee',
        'is_coupon',
        'used_coupon',
        'payment_method',
        'payment_status',
        'final_price',
        'notes',
        'is_returnable',
        'distance',
    ];

    protected $casts = [
        'is_coupon' => 'boolean',
        'is_returnable' => 'boolean',
        'total_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'distance' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }


    public function deliveryAgent()
    {
        return $this->belongsTo(DeliveryAgent::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returns()
    {
        return $this->hasMany(Returns::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    // public function deliveryAgentOrders()
    // {
    //     return $this->hasMany(DeliveryAgentOrder::class);
    // }

    public function deliveryAgentOrders()
    {
        return $this->hasMany(DeliveryAgentOrder::class, 'order_id');
    }
    // Custom Methods
    public function updateStatus($status, $userId)
    {
        $this->update(['status' => $status]);
        $this->statusLogs()->create([
            'status' => $status,
            'changed_by' => $userId,
        ]);
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->total_price, 2);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWithCustomerAndVendor($query)
    {
        return $query->with(['customer', 'vendor']);
    }

    public function scopeHasActiveDelivery($query)
    {
        return $query->whereIn('status', ['on_the_way', 'picked_up']);
    }
}
