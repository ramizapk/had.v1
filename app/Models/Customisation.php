<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'note',
        'vendor_id',
        'is_multi_select'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customProducts()
    {
        return $this->hasMany(CustomProduct::class);
    }
}
