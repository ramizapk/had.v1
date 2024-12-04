<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_item_id',
        'quantity',
        'unit_price',
        'is_discount',
        'unit_discount_price',
        'unit_price_after_discount',
        'total_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_discount_price' => 'decimal:2',
        'unit_price_after_discount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function customisations()
    {
        return $this->hasMany(OrderItemCustomisation::class);
    }

    // Custom Methods
    public function calculateTotalPrice()
    {
        $total = $this->quantity * $this->unit_price_after_discount;
        return $total;
    }
}
