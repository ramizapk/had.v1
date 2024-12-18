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
        'unit_price',
        'total_price',
        'total_customisation_price',
    ];

    // Relationships
    public function returnOrder()
    {
        return $this->belongsTo(Returns::class, 'return_id'); // Use 'return_id' as foreign key
    }



    public function returns()
    {
        return $this->belongsTo(Returns::class, 'return_id');
    }
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
