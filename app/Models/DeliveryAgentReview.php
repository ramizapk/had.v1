<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAgentReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_agent_id',
        'customer_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'integer',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
