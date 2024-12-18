<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryAgentReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'returns_id',
        'delivery_agent_id',
        'is_accepted',
        'is_rejected',
        'accepted_at',
        'rejected_at',
    ];

    public function order()
    {
        return $this->belongsTo(Returns::class, 'order_id');
    }

    public function deliveryAgent()
    {
        return $this->belongsTo(DeliveryAgent::class);
    }

    // Custom Methods
    public function acceptOrder()
    {
        $this->update(['is_accepted' => true]);
    }

    public function rejectOrder()
    {
        $this->update(['is_rejected' => true]);
    }
}
