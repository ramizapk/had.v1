<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'note',
        'price',
        'vendor_id',
        'customisation_id',
    ];

    public function customisation()
    {
        return $this->belongsTo(Customisation::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
