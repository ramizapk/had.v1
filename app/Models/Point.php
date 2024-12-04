<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'points',
        'sticker_25',
        'sticker_50',
        'sticker_75',
        'sticker_100',
        'date_reset_last',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
