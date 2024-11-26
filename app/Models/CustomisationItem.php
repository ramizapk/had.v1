<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomisationItem extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'customisation_id',
        'product_id',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function customisation()
    {
        return $this->belongsTo(Customisation::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'id');
    }
}
