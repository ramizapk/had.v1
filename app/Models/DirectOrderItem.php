<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class DirectOrderItem extends Model
{
    use HasFactory;

    // الحقول القابلة للتعبئة
    protected $fillable = [
        'direct_order_id',
        'name',
        'quantity',
    ];

    /**
     * العلاقة مع الطلب المباشر.
     */
    public function directOrder()
    {
        return $this->belongsTo(DirectOrder::class);
    }
}
