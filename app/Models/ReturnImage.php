<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'returns_id',
        'image_url',
    ];

    // Relationships
    public function returnOrder()
    {
        return $this->belongsTo(Returns::class);
    }
}
