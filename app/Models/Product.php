<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'description',
        'publish',
        'vendor_id',
        'category_id',
        'created_by',
        'updated_by',
        'is_offer',
        'offer_price',
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function items()
    {
        return $this->hasMany(ProductItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeWithImagesAndCustomisations($query)
    {
        return $query->with(['images', 'items.customisations']);
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    public function getDetailedItemsWithCustomisations()
    {
        return $this->items()->with(['customisations.customisation', 'customisations.customisation.customProducts'])->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'customisations' => $item->customisations->map(function ($customisationItem) {
                    return [
                        'customisation_id' => $customisationItem->customisation_id,
                        'customisation_name' => $customisationItem->customisation->name,
                        'items' => $customisationItem->customisation->customProducts->map(function ($customProduct) {
                            return [
                                'id' => $customProduct->id,
                                'name' => $customProduct->name,
                                'price' => $customProduct->price,
                                'note' => $customProduct->note,
                            ];
                        }),
                    ];
                }),
            ];
        });
    }
}
