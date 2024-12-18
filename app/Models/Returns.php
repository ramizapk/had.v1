<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returns extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'vendor_id',
        'delivery_agent_id',
        'delivery_fee',
        'customer_location',
        'customer_latitude',
        'customer_longitude',
        'distance',
        'status',
        'reason',
        'return_price',
        'payment_method',
        'payment_status',
    ];
    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'distance' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function deliveryAgent()
    {
        return $this->belongsTo(DeliveryAgent::class);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class, 'return_id'); // اسم العمود الصحيح
    }


    public function returnImages()
    {
        return $this->hasMany(ReturnImage::class);
    }

    // Custom Methods
    public function getReturnStatus()
    {
        return $this->status == 'pending' ? 'Processing' : ucfirst($this->status);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ReturnStatusLog::class);
    }

    public function deliveryAgentReturns()
    {
        return $this->hasMany(DeliveryAgentReturn::class, 'returns_id');
    }

    public function updateStatus($status, $userId)
    {
        $this->update(['status' => $status]);
        $this->statusLogs()->create([
            'status' => $status,
            'changed_by' => $userId,
        ]);
    }
}
