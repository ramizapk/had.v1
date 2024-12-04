<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returns extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_agent_id',
        'delivery_fee',
        'distance',
        'status',
        'reason',
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

    public function deliveryAgent()
    {
        return $this->belongsTo(DeliveryAgent::class);
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
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
}
