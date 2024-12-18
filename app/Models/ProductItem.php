<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'publish',
        'price',
        'quantity',
        'discount',
        'discount_type',
        'discount_amount',
        'discount_percent',
        'date_from',
        'date_to',
        'product_id',
        'created_by',
        'updated_by',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customisations()
    {
        return $this->hasMany(CustomisationItem::class, 'product_id');
    }

    public function getActivePriceAttribute()
    {
        $now = now();
        // التحقق من وجود خصم صالح
        if (
            $this->discount_type !== 'none' && // التحقق من نوع الخصم
            $this->date_from !== null &&
            $this->date_to !== null &&
            $now->between($this->date_from, $this->date_to)
        ) {
            // حساب السعر بناءً على نوع الخصم
            if ($this->discount_type === 'percentage') {
                $discountAmount = ($this->price * $this->discount_percent) / 100;
                return max(0, $this->price - $discountAmount); // ضمان أن السعر لا يقل عن صفر
            } elseif ($this->discount_type === 'fixed') {
                return max(0, $this->price - $this->discount_amount); // خصم مبلغ ثابت
            }
        }

        return $this->price; // إذا لم يكن هناك خصم صالح
    }

    public function getHasActiveDiscountAttribute()
    {
        $now = now();
        return $this->discount_type !== 'none' &&
            $this->date_from !== null &&
            $this->date_to !== null &&
            $now->between($this->date_from, $this->date_to);
    }


    public function calculateFinalDiscount()
    {
        $now = now();

        // تحقق من صلاحية الخصم
        if (
            $this->discount_type !== 'none' &&
            $this->date_from !== null &&
            $this->date_to !== null &&
            $now->between($this->date_from, $this->date_to)
        ) {
            if ($this->discount_type === 'percentage') {
                // خصم النسبة المئوية
                $discountValue = ($this->price * $this->discount_percent) / 100;
            } elseif ($this->discount_type === 'fixed') {
                // خصم القيمة الثابتة
                $discountValue = $this->discount_amount;
            }

            // احسب السعر النهائي بعد الخصم، مع ضمان أن لا يقل عن صفر
            $finalDiscount = max(0, $this->price - $discountValue);

            // تحديث حقل الخصم في قاعدة البيانات
            $this->update(['discount' => $finalDiscount]);

            return $finalDiscount;
        }

        // في حال عدم وجود خصم، يتم وضع نفس السعر كقيمة للخصم
        $this->update(['discount' => $this->price]);
        return $this->price;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }


}
