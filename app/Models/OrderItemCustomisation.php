<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemCustomisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'customisation_id',
        'custom_product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function customisation()
    {
        return $this->belongsTo(Customisation::class);
    }

    public function customProduct()
    {
        return $this->belongsTo(CustomProduct::class);
    }

    // Custom Methods
    public function calculateTotalPrice()
    {
        return $this->quantity * $this->unit_price;
    }
}
