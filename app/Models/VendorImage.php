<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
class VendorImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vendor_images';
    protected $fillable = [
        'vendor_id',
        'image_url',
    ];


    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }
}
