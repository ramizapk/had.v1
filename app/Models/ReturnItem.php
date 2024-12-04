<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'order_item_id',
        'quantity',
    ];

    // Relationships
    public function returnOrder()
    {
        return $this->belongsTo(Returns::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
