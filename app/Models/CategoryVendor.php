<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CategoryVendor extends Model
{
    use HasFactory;

    protected $table = 'category_vendor';
    public $timestamps = false;
    protected $fillable = [
        'category_id',
        'vendor_id',
        'show_in_menu',
    ];

    /**
     * علاقة مع جدول Category.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * علاقة مع جدول Vendor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
